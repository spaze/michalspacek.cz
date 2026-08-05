<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Encryption;

final readonly class ColumnReEncryptionResult
{

	/**
	 * @param list<int> $failedIds
	 */
	public function __construct(
		public int $reEncrypted,
		public int $upToDate,
		public array $failedIds,
		public int $changedMeanwhile,
	) {
	}

}
