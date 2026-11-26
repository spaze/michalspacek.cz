<?php
declare(strict_types = 1);

namespace Spaze\Session;

use Nette\Database\Explorer;
use Nette\Database\Table\ActiveRow;
use SessionHandlerInterface;
use Spaze\Encryption\SymmetricKeyEncryption;
use Spaze\Session\Exceptions\SessionColumnUnexpectedTypeException;
use Spaze\Session\Exceptions\SessionLockException;
use Throwable;

/**
 * Storing session to database.
 * Inspired by: https://github.com/JedenWeb/SessionStorage/
 */
class MysqlSessionHandler implements SessionHandlerInterface
{

	private ?SymmetricKeyEncryption $encryptionService = null;

	private string $tableName;

	private int $lockTimeout = 5;

	private int $unchangedUpdateDelay = 300;

	private ?string $lockId = null;

	/** Whether the session is being started: open() was called, read() not yet */
	private bool $starting = false;

	/** @var array<string, string> session id => session id hash */
	private array $idHashes = [];

	/** @var array<string, ?ActiveRow> session id => session ActiveRow or null if the row does not exist */
	private array $rows = [];

	/** @var array<string, string> session id => session data */
	private array $data = [];

	/** @var array<string, mixed> column name => column data */
	private array $additionalData = [];

	/**
	 * Occurs before the data is written to session.
	 *
	 * @var list<callable(): void>
	 */
	public array $onBeforeDataWrite = [];


	public function __construct(
		private Explorer $explorer,
	) {
	}


	public function setTableName(string $tableName): void
	{
		$this->tableName = $tableName;
	}


	public function setLockTimeout(int $timeout): void
	{
		$this->lockTimeout = $timeout;
	}


	public function setUnchangedUpdateDelay(int $delay): void
	{
		$this->unchangedUpdateDelay = $delay;
	}


	public function setEncryptionService(SymmetricKeyEncryption $encryptionService): void
	{
		$this->encryptionService = $encryptionService;
	}


	public function setAdditionalData(string $key, mixed $value): void
	{
		$this->additionalData[$key] = $value;
	}


	private function hash(string $id, bool $rawOutput = true): string
	{
		if (!isset($this->idHashes[$id])) {
			$this->idHashes[$id] = \hash('sha256', $id, true);
		}
		return ($rawOutput ? $this->idHashes[$id] : \bin2hex($this->idHashes[$id]));
	}


	private function lock(): void
	{
		$sessionId = \session_id();
		if (!$sessionId) {
			return;
		}
		$lockId = $this->hash($sessionId, false);
		if ($this->lockId !== $lockId) {
			$this->unlock();
			// 1 if the lock was acquired, 0 if it timed out, null on some errors, most errors throw an exception
			$result = $this->explorer->query('SELECT GET_LOCK(?, ?) as `lock`', $lockId, $this->lockTimeout)->fetchField();
			// The result is a string with some connection options
			$result = \is_numeric($result) ? (int)$result : null;
			if ($result !== 1) {
				throw new SessionLockException($this->lockTimeout, $result === 0);
			}
			$this->lockId = $lockId;
		}
	}


	private function unlock(): void
	{
		if ($this->lockId === null) {
			return;
		}

		$lockId = $this->lockId;
		// Forget the lock first, the query fails mostly when the connection is gone, and the lock with it
		$this->lockId = null;
		$this->explorer->query('SELECT RELEASE_LOCK(?)', $lockId);
	}


	private function getRow(string $id): ?ActiveRow
	{
		if (!\array_key_exists($id, $this->rows)) {
			// The columns are listed explicitly, otherwise Nette may fetch just some of them and refetch the row on access, when it may be gone
			$this->rows[$id] = $this->explorer->table($this->tableName)->select('id, timestamp, data')->get($this->hash($id));
		}
		return $this->rows[$id];
	}


	/**
	 * Rows fetched in a previous open/close cycle may have been changed or deleted by other requests after the lock was released.
	 */
	private function clearCache(): void
	{
		$this->rows = [];
		$this->data = [];
	}


	/**
	 * When read(), write() or destroy() throws, or validateId() while the session is being started, PHP ends the session without calling close(),
	 * because it doesn't call handler methods while an exception is pending. The lock would then be held until the database connection is closed,
	 * or until this handler starts a session with another ID. A subclass that catches these exceptions and keeps the session keeps it without the lock.
	 */
	private function closeAfterException(): void
	{
		try {
			$this->close();
		} catch (Throwable) {
			// The original exception is thrown by the caller, close() fails mostly when the connection is gone, and the lock with it
		}
	}


	public function open(string $path, string $name): bool
	{
		$this->clearCache();
		$this->starting = true;
		$this->lock();
		return true;
	}


	public function close(): bool
	{
		$this->starting = false;
		$this->unlock();
		$this->clearCache();
		return true;
	}


	public function destroy(string $id): bool
	{
		try {
			$hashedSessionId = $this->hash($id);
			$this->explorer->table($this->tableName)->where('id', $hashedSessionId)->delete();
			unset($this->rows[$id], $this->data[$id]);
			$this->unlock();
			return true;
		} catch (Throwable $e) {
			$this->closeAfterException();
			throw $e;
		}
	}


	public function read(string $id): string
	{
		$this->starting = false;
		try {
			$this->lock();
			$row = $this->getRow($id);

			if ($row) {
				if (!is_string($row->data)) {
					throw new SessionColumnUnexpectedTypeException('data', gettype($row->data), 'string');
				}
				$this->data[$id] = ($this->encryptionService ? $this->encryptionService->decrypt($row->data) : $row->data);
				return $this->data[$id];
			}
			return '';
		} catch (Throwable $e) {
			$this->closeAfterException();
			throw $e;
		}
	}


	public function write(string $id, string $data): bool
	{
		try {
			$this->lock();
			$hashedSessionId = $this->hash($id);
			$time = \time();
			foreach ($this->onBeforeDataWrite as $handler) {
				$handler();
			}
			$row = $this->getRow($id);
			if (!isset($this->data[$id]) || $this->data[$id] !== $data || $this->additionalData !== []) {
				if ($this->encryptionService) {
					$data = $this->encryptionService->encrypt($data);
				}
				if ($row) {
					// Not $row->update() because it re-reads the row afterwards and throws if it is gone by then
					$this->explorer->table($this->tableName)->where('id', $hashedSessionId)->update([
						'timestamp' => $time,
						'data' => $data,
					] + $this->additionalData);
				} else {
					// A list with one row, because inserting a single row re-reads it afterwards and throws if it is gone by then
					$this->explorer->table($this->tableName)->insert([
						[
							'id' => $hashedSessionId,
							'timestamp' => $time,
							'data' => $data,
						] + $this->additionalData,
					]);
				}
				unset($this->rows[$id]);
			} elseif ($row) {
				if (!is_int($row->timestamp)) {
					throw new SessionColumnUnexpectedTypeException('timestamp', gettype($row->timestamp), 'int');
				}
				if ($this->unchangedUpdateDelay === 0 || $time - $row->timestamp > $this->unchangedUpdateDelay) {
					// Optimization: When data has not been changed, only update
					// the timestamp after a configured delay, if any.
					$this->explorer->table($this->tableName)->where('id', $hashedSessionId)->update([
						'timestamp' => $time,
					]);
					unset($this->rows[$id]);
				}
			}

			return true;
		} catch (Throwable $e) {
			$this->closeAfterException();
			throw $e;
		}
	}


	public function gc(int $max_lifetime): int|false
	{
		$maxTimestamp = \time() - $max_lifetime;

		// Try to avoid a conflict when running garbage collection simultaneously on two
		// MySQL servers at a very busy site in a master-master replication setup by
		// subtracting one tenth of $maxLifeTime (but at least one day) from $maxTimestamp
		// for each server with reasonably small ID except for the server with ID 1.
		//
		// In a typical master-master replication setup, the server IDs are 1 and 2.
		// There is no subtraction on server 1 and one day (or one tenth of $maxLifeTime)
		// subtraction on server 2.
		$row = $this->explorer->query('SELECT @@server_id as `serverId`')->fetch();
		if ($row && is_int($row->serverId) && $row->serverId > 1 && $row->serverId < 10) {
			$maxTimestamp -= ($row->serverId - 1) * \max(86400, $max_lifetime / 10);
		}

		return $this->explorer->table($this->tableName)
			->where('timestamp < ?', $maxTimestamp)
			->delete();
	}


	// phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps -- the name is dictated by PHP
	public function create_sid(): string
	{
		do {
			$id = \bin2hex(\random_bytes(16));
		} while ($this->validateId($id));
		return $id;
	}


	public function validateId(string $id): bool
	{
		try {
			return $this->getRow($id) !== null;
		} catch (Throwable $e) {
			// Not when called by session_create_id() with an active session, which stays active when this throws, and so does the lock
			if ($this->starting) {
				$this->closeAfterException();
			}
			throw $e;
		}
	}

}
