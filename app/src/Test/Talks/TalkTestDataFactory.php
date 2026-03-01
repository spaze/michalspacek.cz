<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Test\Talks;

use DateTime;

final readonly class TalkTestDataFactory
{

	/**
	 * @return non-empty-array<string, int|string|DateTime|null>
	 */
	public function getDatabaseResultData(): array
	{
		return [
			'id' => 42,
			'localeId' => 1,
			'locale' => 'cs_CZ',
			'translationGroupId' => null,
			'action' => null,
			'title' => 'Title',
			'description' => 'Description',
			'date' => new DateTime('2024-03-02 01:10:00'),
			'duration' => null,
			'href' => null,
			'hasSlides' => 0,
			'slidesHref' => null,
			'slidesEmbed' => null,
			'slidesNote' => null,
			'videoHref' => null,
			'videoThumbnail' => null,
			'videoThumbnailAlternative' => null,
			'videoEmbed' => null,
			'event' => 'Event',
			'eventHref' => null,
			'ogImage' => null,
			'transcript' => null,
			'favorite' => null,
			'slidesTalkId' => null,
			'filenamesTalkId' => null,
			'supersededById' => null,
			'supersededByAction' => null,
			'supersededByTitle' => null,
			'publishSlides' => 0,
		];
	}

}
