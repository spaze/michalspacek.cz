<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Encryption;

use InvalidArgumentException;
use MichalSpacekCz\Database\TypedDatabase;
use Nette\Database\Explorer;
use Throwable;
use Tracy\Debugger;

/**
 * Re-encrypts one encrypted database column, so a service storing encrypted values can implement
 * EncryptedStorage by just saying which table, id column, and value column are its own.
 *
 * Rows are read in batches ordered by id, so a table that has grown large doesn't have to fit in memory at once.
 */
final readonly class ColumnReEncryptor
{

	public function __construct(
		private Explorer $database,
		private TypedDatabase $typedDatabase,
		private int $batchSize,
	) {
		if ($batchSize < 1) {
			throw new InvalidArgumentException("Batch size must be at least 1, {$batchSize} given");
		}
	}


	public function reEncrypt(EncryptedColumn $column, bool $dryRun): ColumnReEncryptionResult
	{
		$reEncrypted = 0;
		$upToDate = 0;
		$failedIds = [];
		$changedMeanwhile = 0;
		$encryption = $column->encryption;
		$table = $column->table;
		$idColumn = $column->idColumn;
		$valueColumn = $column->valueColumn;
		$fromId = 0; // the next id to read, not the last one read, so a row with id 0 is included
		do {
			// An empty value holds nothing to re-encrypt, same as a null one, and trying would fail as a malformed
			// ciphertext on every run without saying why
			$rows = $this->typedDatabase->fetchPairsIntString(
				'SELECT ?name, ?name FROM ?name WHERE ?name IS NOT NULL AND ?name != ? AND ?name >= ? ORDER BY ?name LIMIT ?',
				$idColumn,
				$valueColumn,
				$table,
				$valueColumn,
				$valueColumn,
				'',
				$idColumn,
				$fromId,
				$idColumn,
				$this->batchSize,
			);
			foreach ($rows as $id => $encrypted) {
				// Advance even when the value below is skipped, otherwise the next batch would start at the same row again
				$fromId = $id + 1;
				try {
					if (!$encryption->needsReEncrypt($encrypted)) {
						$upToDate++;
						continue;
					}
					$reEncryptedValue = $encryption->encrypt($encryption->decrypt($encrypted));
				} catch (Throwable $e) {
					// A value that can't be decrypted (a dropped key, corruption) is left unchanged, the rest of the run continues
					Debugger::log($e);
					$failedIds[] = $id;
					continue;
				}
				if ($dryRun) {
					$reEncrypted++;
					continue;
				}
				// The value in the WHERE makes sure a row changed by someone else since the read above is left alone,
				// that write encrypted with the active key anyway and updating would overwrite the newer value
				$result = $this->database->query(
					'UPDATE ?name SET ?name = ? WHERE ?name = ? AND ?name = ?',
					$table,
					$valueColumn,
					$reEncryptedValue,
					$idColumn,
					$id,
					$valueColumn,
					$encrypted,
				);
				if ($result->getRowCount() === 1) {
					$reEncrypted++;
				} else {
					$changedMeanwhile++;
				}
			}
		} while (count($rows) === $this->batchSize);
		return new ColumnReEncryptionResult($reEncrypted, $upToDate, $failedIds, $changedMeanwhile);
	}

}
