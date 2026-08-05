<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Encryption;

use MichalSpacekCz\Application\Cli\CliArgs;
use MichalSpacekCz\Application\Cli\CliArgsProvider;
use Nette\CommandLine\Parser;
use Override;
use PHP_Parallel_Lint\PhpConsoleColor\ConsoleColor;
use Throwable;
use Tracy\Debugger;

final readonly class ReEncryption implements CliArgsProvider
{

	private const string DRY_RUN = '--dry-run';


	/**
	 * @param iterable<EncryptedStorage> $storages
	 */
	public function __construct(
		private iterable $storages,
		private ColumnReEncryptor $columnReEncryptor,
		private CliArgs $cliArgs,
		private ConsoleColor $color,
	) {
	}


	public function run(): int
	{
		$cliArgsError = $this->cliArgs->getError();
		if ($cliArgsError !== null) {
			fprintf(STDERR, "%s\n", $this->color->apply('light_red', $cliArgsError));
			return 3;
		}
		$dryRun = $this->cliArgs->getFlag(self::DRY_RUN);
		$hasFailures = false;
		foreach ($this->storages as $storage) {
			$storageLabel = $storage->getEncryptedDataLabel();
			$columns = $storage->getEncryptedColumns();
			foreach ($columns as $column) {
				// The column only when the storage has more than one, it says nothing extra when there is just one
				$label = count($columns) > 1 ? sprintf('%s, %s', $storageLabel, $column->valueColumn) : $storageLabel;
				try {
					$result = $this->columnReEncryptor->reEncrypt($column, $dryRun);
				} catch (Throwable $e) {
					Debugger::log($e);
					fprintf(STDERR, "%s: %s\n", $label, $this->color->apply('light_red', $e->getMessage()));
					$hasFailures = true;
					continue;
				}
				$failedCount = count($result->failedIds);
				if ($failedCount > 0) {
					$hasFailures = true;
				}
				// Written so it can go straight into a query, those rows have to be sorted out by hand before the old key can be dropped
				$failed = $failedCount > 0
					? sprintf(
						$failedCount === 1 ? '%d failed (`%s.%s = %s`)' : '%d failed (`%s.%s IN (%s)`)',
						$failedCount,
						$column->table,
						$column->idColumn,
						implode(', ', $result->failedIds),
					)
					: '0 failed';
				$parts = [
					sprintf($dryRun ? '%d would be re-encrypted' : '%d re-encrypted', $result->reEncrypted),
					sprintf('%d up to date', $result->upToDate),
					$failedCount > 0 ? $this->color->apply('light_red', $failed) : $failed,
				];
				if (!$dryRun) {
					$parts[] = sprintf('%d changed meanwhile', $result->changedMeanwhile);
				}
				printf("%s: %s\n", $label, implode(', ', $parts));
			}
		}
		return $hasFailures ? 1 : 0;
	}


	#[Override]
	public static function defineArgs(Parser $parser): void
	{
		$parser->addSwitch(self::DRY_RUN);
	}

}
