<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Training\Files;

use DateTime;
use Nette\Database\Row;
use SplFileInfo;

final readonly class TrainingFileFactory
{

	public function __construct(
		private TrainingFilesStorage $storage,
	) {
	}


	public function fromDatabaseRow(Row $row): TrainingFile
	{
		assert(is_int($row->fileId));
		assert(is_string($row->fileName));
		assert($row->start instanceof DateTime);
		assert($row->added instanceof DateTime);

		return new TrainingFile(
			$row->fileId,
			$row->fileName,
			new SplFileInfo($this->storage->getFilesDir($row->start) . $row->fileName),
			$row->added,
		);
	}

}
