<?php
declare(strict_types = 1);

namespace Spaze\Session;

use Nette\Database\Explorer;
use Nette\Database\Table\ActiveRow;
use Nette\SmartObject;
use SessionHandlerInterface;
use Spaze\Encryption\Symmetric\StaticKey as StaticKeyEncryption;

/**
 * Storing session to database.
 * Inspired by: https://github.com/JedenWeb/SessionStorage/
 *
 * @method onBeforeDataWrite()
 */
class MysqlSessionHandler implements SessionHandlerInterface
{

	use SmartObject;


	private ?StaticKeyEncryption $encryptionService = null;

	private string $tableName;

	private int $lockTimeout = 5;

	private int $unchangedUpdateDelay = 300;

	private ?string $lockId = null;

	/** @var string[] */
	private array $idHashes = [];

	/** @var ActiveRow{data:string, timestamp:int}|null */
	private ?ActiveRow $row;

	/** @var string[] */
	private array $data = [];

	/** @var mixed[] */
	private array $additionalData = [];

	/**
	 * Occurs before the data is written to session.
	 *
	 * @var callable[] function ()
	 */
	public array $onBeforeDataWrite;


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


	public function setEncryptionService(StaticKeyEncryption $encryptionService): void
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
		if ($this->lockId === null) {
			$sessionId = \session_id();
			if ($sessionId) {
				$this->lockId = $this->hash($sessionId, false);
				$this->explorer->query('SELECT GET_LOCK(?, ?) as `lock`', $this->lockId, $this->lockTimeout);
			}
		}
	}


	private function unlock(): void
	{
		if ($this->lockId === null) {
			return;
		}

		$this->explorer->query('SELECT RELEASE_LOCK(?)', $this->lockId);
		$this->lockId = null;
	}


	public function open(string $path, string $name): bool
	{
		$this->lock();
		return true;
	}


	public function close(): bool
	{
		$this->unlock();
		return true;
	}


	public function destroy(string $id): bool
	{
		$hashedSessionId = $this->hash($id);
		$this->explorer->table($this->tableName)->where('id', $hashedSessionId)->delete();
		$this->unlock();
		return true;
	}


	public function read(string $id): string
	{
		$this->lock();
		$hashedSessionId = $this->hash($id);
		$this->row = $this->explorer->table($this->tableName)->get($hashedSessionId);

		if ($this->row) {
			$this->data[$id] = ($this->encryptionService ? $this->encryptionService->decrypt($this->row->data) : $this->row->data);
			return $this->data[$id];
		}
		return '';
	}


	public function write(string $id, string $data): bool
	{
		$this->lock();
		$hashedSessionId = $this->hash($id);
		$time = \time();

		if (!isset($this->data[$id]) || $this->data[$id] !== $data) {
			if ($this->encryptionService) {
				$data = $this->encryptionService->encrypt($data);
			}
			$this->onBeforeDataWrite();
			$row = $this->explorer->table($this->tableName)->get($hashedSessionId);
			if ($row) {
				$row->update([
					'timestamp' => $time,
					'data' => $data,
				] + $this->additionalData);
			} else {
				$this->explorer->table($this->tableName)->insert([
					'id' => $hashedSessionId,
					'timestamp' => $time,
					'data' => $data,
				] + $this->additionalData);
			}
		} elseif ($this->row && ($this->unchangedUpdateDelay === 0 || $time - $this->row->timestamp > $this->unchangedUpdateDelay)) {
			// Optimization: When data has not been changed, only update
			// the timestamp after a configured delay, if any.
			$this->row->update([
				'timestamp' => $time,
			]);
		}

		return true;
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
		if ($row && $row->serverId > 1 && $row->serverId < 10) {
			$maxTimestamp -= ($row->serverId - 1) * \max(86400, $max_lifetime / 10);
		}

		return $this->explorer->table($this->tableName)
			->where('timestamp < ?', $maxTimestamp)
			->delete();
	}

}
