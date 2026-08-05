<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Encryption;

use Spaze\Encryption\SymmetricKeyEncryption;

/**
 * One encrypted database column, everything ColumnReEncryptor needs to sweep it and the report needs to name it.
 */
final readonly class EncryptedColumn
{

	public function __construct(
		public SymmetricKeyEncryption $encryption,
		public string $table,
		public string $idColumn,
		public string $valueColumn,
	) {
	}

}
