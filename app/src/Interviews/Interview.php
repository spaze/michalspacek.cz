<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Interviews;

use DateTime;
use MichalSpacekCz\Media\Video;
use Nette\Utils\Html;

final readonly class Interview
{

	public function __construct(
		private int $id,
		private string $action,
		private string $title,
		private ?string $descriptionTexy,
		private ?Html $description,
		private DateTime $date,
		private string $href,
		private ?string $audioHref,
		private ?string $audioEmbed,
		private Video $video,
		private ?string $videoEmbed,
		private string $sourceName,
		private string $sourceHref,
	) {
	}


	public function getId(): int
	{
		return $this->id;
	}


	public function getAction(): string
	{
		return $this->action;
	}


	public function getTitle(): string
	{
		return $this->title;
	}


	public function getDescription(): ?Html
	{
		return $this->description;
	}


	public function getDescriptionTexy(): ?string
	{
		return $this->descriptionTexy;
	}


	public function getDate(): DateTime
	{
		return $this->date;
	}


	public function getHref(): string
	{
		return $this->href;
	}


	public function getAudioHref(): ?string
	{
		return $this->audioHref;
	}


	public function getAudioEmbed(): ?string
	{
		return $this->audioEmbed;
	}


	public function getVideo(): Video
	{
		return $this->video;
	}


	public function getVideoEmbed(): ?string
	{
		return $this->videoEmbed;
	}


	public function getSourceName(): string
	{
		return $this->sourceName;
	}


	public function getSourceHref(): string
	{
		return $this->sourceHref;
	}

}
