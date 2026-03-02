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
			'translationGroupId' => $translationGroupId ?? null,
			'action' => $action ?? null,
			'title' => $title ?? 'Title',
			'description' => $description ?? 'Description',
			'date' => $date ?? new DateTime('2024-03-02 01:10:00'),
			'duration' => $duration ?? null,
			'href' => $href ?? null,
			'hasSlides' => $hasSlides ?? 0,
			'slidesHref' => $slidesHref ?? null,
			'slidesEmbed' => $slidesEmbed ?? null,
			'slidesNote' => $slidesNote ?? null,
			'videoHref' => $videoHref ?? null,
			'videoThumbnail' => $videoThumbnail ?? null,
			'videoThumbnailAlternative' => $videoThumbnailAlternative ?? null,
			'videoEmbed' => $videoEmbed ?? null,
			'event' => $event ?? 'Event',
			'eventHref' => $eventHref ?? null,
			'ogImage' => $ogImage ?? null,
			'transcript' => $transcript ?? null,
			'favorite' => $favorite ?? null,
			'slidesTalkId' => $slidesTalkId ?? null,
			'filenamesTalkId' => $filenamesTalkId ?? null,
			'supersededById' => $supersededById ?? null,
			'supersededByAction' => $supersededByAction ?? null,
			'supersededByTitle' => $supersededByTitle ?? null,
			'publishSlides' => $publishSlides ?? 0,
		];
	}

}
