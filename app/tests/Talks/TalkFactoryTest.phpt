<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace Talks;

use DateTime;
use MichalSpacekCz\Talks\TalkFactory;
use MichalSpacekCz\Test\TestCaseRunner;
use Nette\Database\Row;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../bootstrap.php';

/** @testCase */
final class TalkFactoryTest extends TestCase
{

	public function __construct(
		private readonly TalkFactory $talkFactory,
	) {
	}


	public function testCreateFromDatabaseRow(): void
	{
		$row = new Row();
		$row->id = 303;
		$row->localeId = 808;
		$row->locale = 'fo_BR';
		$row->translationGroupId = 909;
		$row->action = 'le-action';
		$row->title = '**Title**';
		$row->description = '//Description//';
		$row->date = new DateTime('2026-01-02 03:04:05');
		$row->duration = 42;
		$row->href = 'https://example.com/';
		$row->hasSlides = 1;
		$row->slidesHref = 'https://example.com/slides';
		$row->slidesEmbed = 'slides-embed';
		$row->slidesNote = '**Notes**';
		$row->videoHref = 'https://example.com/video';
		$row->videoThumbnail = 'thumbnail.jpg';
		$row->videoThumbnailAlternative = 'thumbnail.webp';
		$row->videoEmbed = 'video-embed';
		$row->event = '**Event**';
		$row->eventHref = 'https://example.com/event';
		$row->ogImage = 'og.jpg';
		$row->transcript = '**Transcript**';
		$row->favorite = 'Fav';
		$row->slidesTalkId = 43;
		$row->filenamesTalkId = 44;
		$row->supersededById = 45;
		$row->supersededByAction = 'superseded-action';
		$row->supersededByTitle = 'Superseded By';
		$row->publishSlides = 1;
		$talk = $this->talkFactory->createFromDatabaseRow($row);
		Assert::same(303, $talk->getId());
		Assert::same(808, $talk->getLocaleId());
		Assert::same('fo_BR', $talk->getLocale());
		Assert::same(909, $talk->getTranslationGroupId());
		Assert::same('le-action', $talk->getAction());
		Assert::same('**Title**', $talk->getTitleTexy());
		Assert::same('<strong>Title</strong>', $talk->getTitle()->render());
		Assert::same('//Description//', $talk->getDescriptionTexy());
		Assert::same("<p><em>Description</em></p>\n", $talk->getDescription()?->render());
		Assert::equal(new DateTime('2026-01-02 03:04:05'), $talk->getDate());
		Assert::same(42, $talk->getDuration());
		Assert::same('https://example.com/', $talk->getHref());
		Assert::true($talk->hasSlides());
		Assert::same('https://example.com/slides', $talk->getSlidesHref());
		Assert::same('slides-embed', $talk->getSlidesEmbed());
		Assert::same('**Notes**', $talk->getSlidesNoteTexy());
		Assert::same("<p><strong>Notes</strong></p>\n", $talk->getSlidesNote()?->render());
		Assert::same('https://example.com/video', $talk->getVideo()->getVideoHref());
		Assert::same('thumbnail.jpg', $talk->getVideo()->getThumbnailFilename());
		Assert::same('thumbnail.webp', $talk->getVideo()->getThumbnailAlternativeFilename());
		Assert::same('video-embed', $talk->getVideoEmbed());
		Assert::same('**Event**', $talk->getEventTexy());
		Assert::same('<strong>Event</strong>', $talk->getEvent()->render());
		Assert::same('https://example.com/event', $talk->getEventHref());
		Assert::same('og.jpg', $talk->getOgImage());
		Assert::same('**Transcript**', $talk->getTranscriptTexy());
		Assert::same("<p><strong>Transcript</strong></p>\n", $talk->getTranscript()?->render());
		Assert::same('Fav', $talk->getFavorite());
		Assert::same(43, $talk->getSlidesTalkId());
		Assert::same(44, $talk->getFilenamesTalkId());
		Assert::same(45, $talk->getSupersededById());
		Assert::same('superseded-action', $talk->getSupersededByAction());
		Assert::same('Superseded By', $talk->getSupersededByTitle());
		Assert::true($talk->isPublishSlides());
	}

}

TestCaseRunner::run(TalkFactoryTest::class);
