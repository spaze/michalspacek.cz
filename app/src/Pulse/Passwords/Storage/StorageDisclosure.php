<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Pulse\Passwords\Storage;

use DateTimeInterface;

readonly class StorageDisclosure
{

	public function __construct(
		private int $id,
		private string $url,
		private string $archive,
		private ?string $note,
		private ?DateTimeInterface $published,
		private ?DateTimeInterface $added,
		private string $type,
		private string $typeAlias,
	) {
	}


	public function getId(): int
	{
		return $this->id;
	}


	public function getUrl(): string
	{
		return $this->url;
	}


	public function getArchive(): string
	{
		return $this->archive;
	}


	public function getNote(): ?string
	{
		return $this->note;
	}


	public function getPublished(): ?DateTimeInterface
	{
		return $this->published;
	}


	public function getAdded(): ?DateTimeInterface
	{
		return $this->added;
	}


	public function getType(): string
	{
		return $this->type;
	}


	public function getTypeAlias(): string
	{
		return $this->typeAlias;
	}

}
