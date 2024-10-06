<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Talks;

use DateTime;
use MichalSpacekCz\Media\Video;
use Nette\Utils\Html;

readonly class Talk
{

	public function __construct(
		private int $id,
		private int $localeId,
		private string $locale,
		private ?int $translationGroupId,
		private ?string $action,
		private ?string $url,
		private Html $title,
		private string $titleTexy,
		private ?Html $description,
		private ?string $descriptionTexy,
		private DateTime $date,
		private ?int $duration,
		private ?string $href,
		private bool $hasSlides,
		private ?string $slidesHref,
		private ?string $slidesEmbed,
		private ?Html $slidesNote,
		private ?string $slidesNoteTexy,
		private Video $video,
		private ?string $videoEmbed,
		private Html $event,
		private string $eventTexy,
		private ?string $eventHref,
		private ?string $ogImage,
		private ?Html $transcript,
		private ?string $transcriptTexy,
		private ?string $favorite,
		private ?int $slidesTalkId,
		private ?int $filenamesTalkId,
		private ?int $supersededById,
		private ?string $supersededByAction,
		private ?string $supersededByTitle,
		private bool $publishSlides,
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


	public function getTranslationGroupId(): ?int
	{
		return $this->translationGroupId;
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


	public function hasSlides(): bool
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


	public function getSlidesNote(): ?Html
	{
		return $this->slidesNote;
	}


	public function getSlidesNoteTexy(): ?string
	{
		return $this->slidesNoteTexy;
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
