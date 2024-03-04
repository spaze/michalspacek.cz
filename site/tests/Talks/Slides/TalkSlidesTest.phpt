<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\Talks\Slides;

use DateTime;
use MichalSpacekCz\Media\Video;
use MichalSpacekCz\Talks\Exceptions\TalkSlideDoesNotExistException;
use MichalSpacekCz\Talks\Talk;
use MichalSpacekCz\Test\Database\Database;
use MichalSpacekCz\Test\PrivateProperty;
use MichalSpacekCz\Test\TestCaseRunner;
use Nette\Http\FileUpload;
use Nette\Utils\ArrayHash;
use Nette\Utils\Html;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../../bootstrap.php';

/** @testCase */
class TalkSlidesTest extends TestCase
{

	public function __construct(
		private readonly TalkSlides $talkSlides,
		private readonly Database $database,
	) {
	}


	public function testGetSlideNo(): void
	{
		Assert::null($this->talkSlides->getSlideNo(1, null));

		$this->database->setFetchFieldDefaultResult(null);
		Assert::same(303, $this->talkSlides->getSlideNo(1, '303'));

		$this->database->setFetchFieldDefaultResult(null);
		Assert::exception(function (): void {
			$this->talkSlides->getSlideNo(1, 'yo');
		}, TalkSlideDoesNotExistException::class, "Talk id 1 doesn't have a slide 'yo'");

		$this->database->setFetchFieldDefaultResult(808);
		Assert::same(808, $this->talkSlides->getSlideNo(1, 'yo'));
	}


	public function testSaveSlides(): void
	{
		$slides = new TalkSlideCollection(303);
		$slides->add(new TalkSlide(1, 'slide1', 1, 'slide1.jpg', 'slide-alt.jpg', null, 'Title 1', Html::fromText('Notes 1'), 'Notes 1', null, null, null));
		$slides->add(new TalkSlide(2, 'slide2', 2, 'slide2.jpg', 'slide-alt.jpg', null, 'Title 2', Html::fromText('Notes 2'), 'Notes 2', null, null, null));
		$updateSlides = [
			1 => $this->buildSlideArrayHash(1, 'foo1', 'Title 1', 'Speaker notes 1', 'slide1.jpg', null, 'slide-alt1.jpg', null),
			2 => $this->buildSlideArrayHash(2, 'foo2', 'Title 2', 'Speaker notes 2', 'slide2.jpg', null, 'slide-alt2.jpg', null),
		];
		$newSlides = [
			$this->buildSlideArrayHash(3, 'new1', 'New 1', 'New notes 1', null, new FileUpload(null), null, new FileUpload(null)),
			$this->buildSlideArrayHash(4, 'new2', 'New 2', 'New notes 2', null, new FileUpload(null), null, new FileUpload(null)),
		];
		$this->talkSlides->saveSlides(303, $slides, $updateSlides, $newSlides, false);
		Assert::same(['slide1.jpg' => 0, 'slide-alt.jpg' => 1, 'slide2.jpg' => 0], PrivateProperty::getValue($this->talkSlides, 'otherSlides'));
		$paramsUpdate = $this->database->getParamsArrayForQuery('UPDATE talk_slides SET ? WHERE id_slide = ?');
		$expectedParamsUpdate = [
			[
				'key_talk' => 303,
				'alias' => 'foo1',
				'number' => 1,
				'filename' => '',
				'filename_alternative' => '',
				'title' => 'Title 1',
				'speaker_notes' => 'Speaker notes 1',
			],
			[
				'key_talk' => 303,
				'alias' => 'foo2',
				'number' => 2,
				'filename' => '',
				'filename_alternative' => '',
				'title' => 'Title 2',
				'speaker_notes' => 'Speaker notes 2',
			],
		];
		Assert::same($expectedParamsUpdate, $paramsUpdate);
		$paramsInsert = $this->database->getParamsArrayForQuery('INSERT INTO talk_slides');
		$expectedParamsInsert = [
			[
				'key_talk' => 303,
				'alias' => 'new1',
				'number' => 3,
				'filename' => '',
				'filename_alternative' => '',
				'title' => 'New 1',
				'speaker_notes' => 'New notes 1',
			],
			[
				'key_talk' => 303,
				'alias' => 'new2',
				'number' => 4,
				'filename' => '',
				'filename_alternative' => '',
				'title' => 'New 2',
				'speaker_notes' => 'New notes 2',
			],
		];
		Assert::same($expectedParamsInsert, $paramsInsert);
	}


	public function testGetSlides(): void
	{
		$this->database->addFetchAllResult([
			[
				'id' => 123,
				'alias' => 'alias-1',
				'number' => 3,
				'filename' => 'filename1.jpg',
				'filenameAlternative' => 'filename1.webp',
				'title' => 'Title 1',
				'speakerNotesTexy' => 'speaker **notes** 1',
			],
			[
				'id' => 124,
				'alias' => 'alias-2',
				'number' => 4,
				'filename' => 'filename2.jpg',
				'filenameAlternative' => null,
				'title' => 'Title 2',
				'speakerNotesTexy' => 'speaker **notes** 2',
			],
		]);
		$slides = $this->talkSlides->getSlides($this->buildTalk(456, null));
		$slide3 = $slides->getByNumber(3);
		$slide4 = $slides->getByNumber(4);
		Assert::same('filename1.jpg', $slide3->getFilename());
		Assert::same('filename1.webp', $slide3->getFilenameAlternative());
		Assert::same('speaker **notes** 1', $slide3->getSpeakerNotesTexy());
		Assert::same('speaker <strong>notes</strong> 1', $slide3->getSpeakerNotes()->render());
		Assert::null($slide3->getFilenamesTalkId());
		Assert::same('https://www.domain.example/i/images/talks/456/filename1.jpg', $slide3->getImage());
		Assert::same('https://www.domain.example/i/images/talks/456/filename1.webp', $slide3->getImageAlternative());
		Assert::same('image/webp', $slide3->getImageAlternativeType());
		Assert::same('filename2.jpg', $slide4->getFilename());
		Assert::null($slide4->getFilenameAlternative());
		Assert::same('speaker **notes** 2', $slide4->getSpeakerNotesTexy());
		Assert::same('speaker <strong>notes</strong> 2', $slide4->getSpeakerNotes()->render());
		Assert::null($slide4->getFilenamesTalkId());
		Assert::same('https://www.domain.example/i/images/talks/456/filename2.jpg', $slide4->getImage());
		Assert::null($slide4->getImageAlternative());
		Assert::null($slide4->getImageAlternativeType());
	}


	public function testGetSlidesWithFilenamesTalkId(): void
	{
		$this->database->addFetchAllResult([
			[
				'id' => 123,
				'alias' => 'alias-1',
				'number' => 3,
				'filename' => 'filename.jpg',
				'filenameAlternative' => 'filename.webp',
				'title' => 'Title 1',
				'speakerNotesTexy' => 'speaker **notes** 1',
			],
		]);
		$this->database->addFetchAllResult([
			[
				'number' => 3,
				'filename' => 'other-talk.jpg',
				'filenameAlternative' => 'other-talk.webp',
			],
		]);
		$slide3 = $this->talkSlides->getSlides($this->buildTalk(456, 789))->getByNumber(3);
		Assert::same('other-talk.jpg', $slide3->getFilename());
		Assert::same('other-talk.webp', $slide3->getFilenameAlternative());
		Assert::same(789, $slide3->getFilenamesTalkId());
		Assert::same('https://www.domain.example/i/images/talks/789/other-talk.jpg', $slide3->getImage());
		Assert::same('https://www.domain.example/i/images/talks/789/other-talk.webp', $slide3->getImageAlternative());
		Assert::same('image/webp', $slide3->getImageAlternativeType());
	}


	private function buildTalk(int $id, ?int $filenamesTalkId): Talk
	{
		$video = new Video(
			null,
			null,
			null,
			null,
			null,
			null,
			320,
			200,
			null,
		);
		return new Talk(
			$id,
			1,
			'cs_CZ',
			null,
			null,
			null,
			Html::fromText('title'),
			'title',
			null,
			null,
			new DateTime(),
			null,
			null,
			false,
			null,
			null,
			null,
			null,
			$video,
			null,
			Html::fromText('event'),
			'event',
			null,
			null,
			null,
			null,
			null,
			null,
			$filenamesTalkId,
			null,
			null,
			null,
			false,
		);
	}


	/**
	 * @return ArrayHash<int|string|FileUpload|null>
	 */
	private function buildSlideArrayHash(
		int $number,
		string $alias,
		string $title,
		string $speakerNotes,
		?string $filename,
		?FileUpload $replace,
		?string $filenameAlternative,
		?FileUpload $replaceAlternative,
	): ArrayHash {
		/** @var ArrayHash<int|string|FileUpload|null> $arrayHash */
		$arrayHash = new ArrayHash();
		$arrayHash->number = $number;
		$arrayHash->alias = $alias;
		$arrayHash->title = $title;
		$arrayHash->speakerNotes = $speakerNotes;
		$arrayHash->filename = $filename;
		$arrayHash->replace = $replace;
		$arrayHash->filenameAlternative = $filenameAlternative;
		$arrayHash->replaceAlternative = $replaceAlternative;
		return $arrayHash;
	}

}

TestCaseRunner::run(TalkSlidesTest::class);
