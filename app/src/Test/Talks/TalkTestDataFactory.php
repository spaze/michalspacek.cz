<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Test\Talks;

use DateTime;

final readonly class TalkTestDataFactory
{

	/**
	 * @return non-empty-array<string, int|string|DateTime|null>
	 */
	public function getDatabaseResultData(
		?int $id = null,
		?int $localeId = null,
		?string $locale = null,
		?int $translationGroupId = null,
		?string $action = null,
		?string $title = null,
		?string $description = null,
		?DateTime $date = null,
		?int $duration = null,
		?string $href = null,
		?int $hasSlides = null,
		?string $slidesHref = null,
		?string $slidesEmbed = null,
		?string $slidesNote = null,
		?string $videoHref = null,
		?string $videoThumbnail = null,
		?string $videoThumbnailAlternative = null,
		?string $videoEmbed = null,
		?string $event = null,
		?string $eventHref = null,
		?string $ogImage = null,
		?string $transcript = null,
		?string $favorite = null,
		?int $slidesTalkId = null,
		?int $filenamesTalkId = null,
		?int $supersededById = null,
		?string $supersededByAction = null,
		?string $supersededByTitle = null,
		?int $publishSlides = null,
	): array {
		return [
			'id' => $id ?? 42,
			'localeId' => $localeId ?? 1,
			'locale' => $locale ?? 'cs_CZ',
			'translationGroupId' => $translationGroupId,
			'action' => $action,
			'title' => $title ?? 'Title',
			'description' => $description ?? 'Description',
			'date' => $date ?? new DateTime('2024-03-02 01:10:00'),
			'duration' => $duration,
			'href' => $href,
			'hasSlides' => $hasSlides ?? 0,
			'slidesHref' => $slidesHref,
			'slidesEmbed' => $slidesEmbed,
			'slidesNote' => $slidesNote,
			'videoHref' => $videoHref,
			'videoThumbnail' => $videoThumbnail,
			'videoThumbnailAlternative' => $videoThumbnailAlternative,
			'videoEmbed' => $videoEmbed,
			'event' => $event ?? 'Event',
			'eventHref' => $eventHref,
			'ogImage' => $ogImage,
			'transcript' => $transcript,
			'favorite' => $favorite,
			'slidesTalkId' => $slidesTalkId,
			'filenamesTalkId' => $filenamesTalkId,
			'supersededById' => $supersededById,
			'supersededByAction' => $supersededByAction,
			'supersededByTitle' => $supersededByTitle,
			'publishSlides' => $publishSlides ?? 0,
		];
	}

}
