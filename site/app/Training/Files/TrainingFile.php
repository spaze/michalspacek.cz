<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Training\Files;

use DateTimeImmutable;
use DateTimeInterface;
use SplFileInfo;

class TrainingFile
{

	private DateTimeImmutable $added;


	public function __construct(
		private int $id,
		private string $filename,
		private SplFileInfo $fileInfo,
		DateTimeInterface $added,
	) {
		$this->added = DateTimeImmutable::createFromInterface($added);
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


	public function getAdded(): DateTimeImmutable
	{
		return $this->added;
	}

}
