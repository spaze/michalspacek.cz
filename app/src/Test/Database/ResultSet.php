<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Test\Database;

use Nette\Database\ResultSet as NetteResultSet;
use Override;

class ResultSet extends NetteResultSet
{

	/** @noinspection PhpMissingParentConstructorInspection intentionally */
	public function __construct(
		private readonly ?int $rowCount = null,
	) {
	}


	#[Override]
	public function getRowCount(): ?int
	{
		return $this->rowCount;
	}

}
