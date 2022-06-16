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
		private readonly int $id,
		private readonly string $filename,
		private readonly SplFileInfo $fileInfo,
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
