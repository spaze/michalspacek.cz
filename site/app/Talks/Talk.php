<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Talks;

use DateTime;
use MichalSpacekCz\Media\Video;
use Nette\Utils\Html;

class Talk
{

	public function __construct(
		private readonly int $id,
		private readonly int $localeId,
		private readonly string $locale,
		private readonly ?string $action,
		private readonly ?string $url,
		private readonly Html $title,
		private readonly string $titleTexy,
		private readonly ?Html $description,
		private readonly ?string $descriptionTexy,
		private readonly DateTime $date,
		private readonly ?int $duration,
		private readonly ?string $href,
		private readonly bool $hasSlides,
		private readonly ?string $slidesHref,
		private readonly ?string $slidesEmbed,
		private readonly Video $video,
		private readonly ?string $videoEmbed,
		private readonly Html $event,
		private readonly string $eventTexy,
		private readonly ?string $eventHref,
		private readonly ?string $ogImage,
		private readonly ?Html $transcript,
		private readonly ?string $transcriptTexy,
		private readonly ?string $favorite,
		private readonly ?int $slidesTalkId,
		private readonly ?int $filenamesTalkId,
		private readonly ?int $supersededById,
		private readonly ?string $supersededByAction,
		private readonly ?string $supersededByTitle,
		private readonly bool $publishSlides,
	) {
	}


	public function getId(): int
	{
		return $this->id;
	}


	public function getLocaleId(): int
	{
		return $this->localeId;
	}


	public function getLocale(): string
	{
		return $this->locale;
	}


	public function getAction(): ?string
	{
		return $this->action;
	}


	public function getUrl(): ?string
	{
		return $this->url;
	}


	public function getTitle(): Html
	{
		return $this->title;
	}


	public function getTitleTexy(): string
	{
		return $this->titleTexy;
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


	public function getDuration(): ?int
	{
		return $this->duration;
	}


	public function getHref(): ?string
	{
		return $this->href;
	}


	public function isHasSlides(): bool
	{
		return $this->hasSlides;
	}


	public function getSlidesHref(): ?string
	{
		return $this->slidesHref;
	}


	public function getSlidesEmbed(): ?string
	{
		return $this->slidesEmbed;
	}


	public function getVideo(): Video
	{
		return $this->video;
	}


	public function getVideoEmbed(): ?string
	{
		return $this->videoEmbed;
	}


	public function getEvent(): Html
	{
		return $this->event;
	}


	public function getEventTexy(): string
	{
		return $this->eventTexy;
	}


	public function getEventHref(): ?string
	{
		return $this->eventHref;
	}


	public function getOgImage(): ?string
	{
		return $this->ogImage;
	}


	public function getTranscript(): ?Html
	{
		return $this->transcript;
	}


	public function getTranscriptTexy(): ?string
	{
		return $this->transcriptTexy;
	}


	public function getFavorite(): ?string
	{
		return $this->favorite;
	}


	public function getSlidesTalkId(): ?int
	{
		return $this->slidesTalkId;
	}


	public function getFilenamesTalkId(): ?int
	{
		return $this->filenamesTalkId;
	}


	public function getSupersededById(): ?int
	{
		return $this->supersededById;
	}


	public function getSupersededByAction(): ?string
	{
		return $this->supersededByAction;
	}


	public function getSupersededByTitle(): ?string
	{
		return $this->supersededByTitle;
	}


	public function isPublishSlides(): bool
	{
		return $this->publishSlides;
	}

}
