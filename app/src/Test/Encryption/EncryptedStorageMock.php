<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Test\Encryption;

use MichalSpacekCz\Encryption\EncryptedColumn;
use MichalSpacekCz\Encryption\EncryptedStorage;
use MichalSpacekCz\Test\WillThrow;
use Override;

final class EncryptedStorageMock implements EncryptedStorage
{

	use WillThrow;


	/**
	 * @param non-empty-string $label
	 * @param non-empty-list<EncryptedColumn> $columns
	 */
	public function __construct(
		private readonly string $label,
		private readonly array $columns,
	) {
	}


	#[Override]
	public function getEncryptedDataLabel(): string
	{
		return $this->label;
	}


	/**
	 * @return non-empty-list<EncryptedColumn>
	 */
	#[Override]
	public function getEncryptedColumns(): array
	{
		$this->maybeThrow();
		return $this->columns;
	}

}
