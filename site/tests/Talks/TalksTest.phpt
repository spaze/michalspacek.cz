<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Talks;

use MichalSpacekCz\Test\Database\Database;
use MichalSpacekCz\Test\TestCaseRunner;
use Override;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../bootstrap.php';

/** @testCase */
class TalksTest extends TestCase
{

	public function __construct(
		private readonly Talks $talks,
		private readonly Database $database,
	) {
	}


	#[Override]
	protected function tearDown(): void
	{
		$this->database->reset();
	}


	public function testAdd(): void
	{
		$this->talks->add(
			2,
			3,
			'le-talk',
			'Le Talk',
			'Talk Desc',
			'2022-04-05 10:20:30',
			60,
			'https://example.com/le-talk',
			10,
			20,
			'https://com.example/talk',
			'https://embed.example/',
			'Slides note',
			'https://video.example/foo',
			'video.thumbnail',
			'video.thumbnail.bmp',
			'https://video.embed.example/foo',
			'Lef Con',
			'https://lefcon.example/',
			'OG Image',
			'Transcript',
			'Fav',
			1337,
			true,
		);
		$this->assertParams(
			2,
			3,
			'le-talk',
			'Le Talk',
			'Talk Desc',
			'2022-04-05 10:20:30',
			60,
			'https://example.com/le-talk',
			10,
			20,
			'https://com.example/talk',
			'https://embed.example/',
			'Slides note',
			'https://video.example/foo',
			'video.thumbnail',
			'video.thumbnail.bmp',
			'https://video.embed.example/foo',
			'Lef Con',
			'https://lefcon.example/',
			'OG Image',
			'Transcript',
			'Fav',
			1337,
			true,
		);
	}


	public function testAddNulls(): void
	{
		$this->talks->add(
			4,
			null,
			null,
			'Le Talk 2',
			null,
			'2023-04-05 10:20:30',
			null,
			null,
			null,
			null,
			null,
			null,
			null,
			null,
			null,
			null,
			null,
			'Zef Con',
			null,
			null,
			null,
			null,
			null,
			false,
		);
		$this->assertParams(
			4,
			null,
			null,
			'Le Talk 2',
			null,
			'2023-04-05 10:20:30',
			null,
			null,
			null,
			null,
			null,
			null,
			null,
			null,
			null,
			null,
			null,
			'Zef Con',
			null,
			null,
			null,
			null,
			null,
			false,
		);
	}


	public function testAddEmptyStrings(): void
	{
		$this->talks->add(
			6,
			0,
			'',
			'Le Talk 3',
			'',
			'2024-04-05 10:20:30',
			0,
			'',
			0,
			0,
			'',
			'',
			'',
			'',
			'',
			'',
			'',
			'Zef Con',
			'',
			'',
			'',
			'',
			0,
			false,
		);
		$this->assertParams(
			6,
			null,
			null,
			'Le Talk 3',
			null,
			'2024-04-05 10:20:30',
			null,
			null,
			null,
			null,
			null,
			null,
			null,
			null,
			'',
			'',
			null,
			'Zef Con',
			null,
			null,
			null,
			null,
			null,
			false,
		);
	}


	private function assertParams(
		int $localeId,
		?int $translationGroupId,
		?string $action,
		string $title,
		?string $description,
		string $date,
		?int $duration,
		?string $href,
		?int $slidesTalk,
		?int $filenamesTalk,
		?string $slidesHref,
		?string $slidesEmbed,
		?string $slidesNote,
		?string $videoHref,
		?string $videoThumbnail,
		?string $videoThumbnailAlternative,
		?string $videoEmbed,
		?string $event,
		?string $eventHref,
		?string $ogImage,
		?string $transcript,
		?string $favorite,
		?int $supersededBy,
		bool $publishSlides,
	): void {
		$params = $this->database->getParamsArrayForQuery('INSERT INTO talks')[0];
		Assert::same($localeId, $params['key_locale']);
		Assert::same($translationGroupId, $params['key_translation_group']);
		Assert::same($action, $params['action']);
		Assert::same($title, $params['title']);
		Assert::same($description, $params['description']);
		Assert::same($date, $params['date']);
		Assert::same($duration, $params['duration']);
		Assert::same($href, $params['href']);
		Assert::same($slidesTalk, $params['key_talk_slides']);
		Assert::same($filenamesTalk, $params['key_talk_filenames']);
		Assert::same($slidesHref, $params['slides_href']);
		Assert::same($slidesEmbed, $params['slides_embed']);
		Assert::same($slidesNote, $params['slides_note']);
		Assert::same($videoHref, $params['video_href']);
		Assert::same($videoThumbnail, $params['video_thumbnail']);
		Assert::same($videoThumbnailAlternative, $params['video_thumbnail_alternative']);
		Assert::same($videoEmbed, $params['video_embed']);
		Assert::same($event, $params['event']);
		Assert::same($eventHref, $params['event_href']);
		Assert::same($ogImage, $params['og_image']);
		Assert::same($transcript, $params['transcript']);
		Assert::same($favorite, $params['favorite']);
		Assert::same($supersededBy, $params['key_superseded_by']);
		Assert::same($publishSlides, $params['publish_slides']);
	}

}

TestCaseRunner::run(TalksTest::class);
