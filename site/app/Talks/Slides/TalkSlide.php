<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Talks\Slides;

use Nette\Utils\Html;

class TalkSlide
{

	public function __construct(
		private readonly int $id,
		private readonly string $alias,
		private readonly int $number,
		private readonly ?string $filename,
		private readonly ?string $filenameAlternative,
		private readonly ?int $filenamesTalkId,
		private readonly string $title,
		private readonly Html $speakerNotes,
		private readonly string $speakerNotesTexy,
		private readonly ?string $image,
		private readonly ?string $imageAlternative,
		private readonly ?string $imageAlternativeType,
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
		if ($this->filename) {
			$filenames[] = $this->filename;
		}
		if ($this->filenameAlternative) {
			$filenames[] = $this->filenameAlternative;
		}
		return $filenames;
	}

}
