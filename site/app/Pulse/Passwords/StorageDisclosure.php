<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Pulse\Passwords;

use DateTimeInterface;

class StorageDisclosure
{

	private int $id;

	private string $url;

	private string $archive;

	private ?string $note;

	private ?DateTimeInterface $published;

	private ?DateTimeInterface $added;

	private string $type;

	private string $typeAlias;


	public function __construct(int $id, string $url, string $archive, ?string $note, ?DateTimeInterface $published, ?DateTimeInterface $added, string $type, string $typeAlias)
	{
		$this->id = $id;
		$this->url = $url;
		$this->archive = $archive;
		$this->note = $note;
		$this->published = $published;
		$this->added = $added;
		$this->type = $type;
		$this->typeAlias = $typeAlias;
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
