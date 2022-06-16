<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Training\Files;

use Nette\Database\Row;
use SplFileInfo;

class TrainingFileFactory
{

	public function __construct(
		private readonly TrainingFilesStorage $storage,
	) {
	}


	public function fromDatabaseRow(Row $row): TrainingFile
	{
		return new TrainingFile(
			$row->fileId,
			$row->fileName,
			new SplFileInfo($this->storage->getFilesDir($row->start) . $row->fileName),
			$row->added,
		);
	}

}
