<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Pulse\Passwords;

use DateTimeInterface;

class StorageDisclosure
{

	public function __construct(
		private readonly int $id,
		private readonly string $url,
		private readonly string $archive,
		private readonly ?string $note,
		private readonly ?DateTimeInterface $published,
		private readonly ?DateTimeInterface $added,
		private readonly string $type,
		private readonly string $typeAlias,
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
