<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Talks\Slides;

use Nette\Utils\Html;

final readonly class TalkSlide
{

	public function __construct(
		private int $id,
		private string $alias,
		private int $number,
		private ?string $filename,
		private ?string $filenameAlternative,
		private ?int $filenamesTalkId,
		private string $title,
		private Html $speakerNotes,
		private string $speakerNotesTexy,
		private ?string $image,
		private ?string $imageAlternative,
		private ?string $imageAlternativeType,
	) {
	}


	public function getId(): int
	{
		return $this->id;
	}


	public function getAlias(): string
	{
		return $this->alias;
	}


	public function getNumber(): int
	{
		return $this->number;
	}


	public function getFilename(): ?string
	{
		return $this->filename;
	}


	public function getFilenameAlternative(): ?string
	{
		return $this->filenameAlternative;
	}


	public function getFilenamesTalkId(): ?int
	{
		return $this->filenamesTalkId;
	}


	public function getTitle(): string
	{
		return $this->title;
	}


	public function getSpeakerNotes(): Html
	{
		return $this->speakerNotes;
	}


	public function getSpeakerNotesTexy(): string
	{
		return $this->speakerNotesTexy;
	}


	public function getImage(): ?string
	{
		return $this->image;
	}


	public function getImageAlternative(): ?string
	{
		return $this->imageAlternative;
	}


	public function getImageAlternativeType(): ?string
	{
		return $this->imageAlternativeType;
	}


	/**
	 * @return list<string>
	 */
	public function getAllFilenames(): array
	{
		$filenames = [];
		if ($this->filename !== null) {
			$filenames[] = $this->filename;
		}
		if ($this->filenameAlternative !== null) {
			$filenames[] = $this->filenameAlternative;
		}
		return $filenames;
	}

}
