<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Training\Files;

use SplFileInfo;

class TrainingFile
{

	public function __construct(
		private int $id,
		private string $filename,
		private SplFileInfo $fileInfo,
	) {
	}


	public function getId(): int
	{
		return $this->id;
	}


	public function getFilename(): string
	{
		return $this->filename;
	}


	public function getFileInfo(): SplFileInfo
	{
		return $this->fileInfo;
	}

}
