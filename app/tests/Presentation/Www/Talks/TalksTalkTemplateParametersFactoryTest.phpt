<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace Presentation\Www\Talks;

use MichalSpacekCz\Media\SlidesPlatform;
use MichalSpacekCz\Presentation\Www\Talks\Exceptions\IncorrectSlideAliasInUrlException;
use MichalSpacekCz\Presentation\Www\Talks\Exceptions\TalkExistsInOtherLocaleException;
use MichalSpacekCz\Presentation\Www\Talks\TalksTalkTemplateParametersFactory;
use MichalSpacekCz\Talks\Exceptions\TalkSlideDoesNotExistException;
use MichalSpacekCz\Talks\Exceptions\TalkSlidesNotPublishedException;
use MichalSpacekCz\Test\Database\Database;
use MichalSpacekCz\Test\NoOpTranslator;
use MichalSpacekCz\Test\Talks\TalkTestDataFactory;
use MichalSpacekCz\Test\TestCaseRunner;
use Override;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../../../bootstrap.php';

/** @testCase */
final class TalksTalkTemplateParametersFactoryTest extends TestCase
{

	public function __construct(
		private readonly TalksTalkTemplateParametersFactory $templateParametersFactory,
		private readonly TalkTestDataFactory $testDataFactory,
		private readonly Database $database,
		private readonly NoOpTranslator $translator,
	) {
	}


	#[Override]
	protected function tearDown(): void
	{
		$this->database->reset();
	}


	public function testCreateTalkOtherLocale(): void
	{
		$this->database->setFetchDefaultResult($this->testDataFactory->getDatabaseResultData(
			locale: 'fo_BA',
		));
		Assert::exception(function (): void {
			$this->templateParametersFactory->create('talk');
		}, TalkExistsInOtherLocaleException::class, "The talk exists in locale 'fo_BA'");
	}


	public function testCreate(): void
	{
		$this->database->addFetchResult($this->testDataFactory->getDatabaseResultData(
			id: 303,
			locale: $this->translator->getDefaultLocale(),
			action: 'some-talk',
			title: 'Talk //Title//',
			slidesHref: 'https://www.slideshare.net/foo-bar',
			publishSlides: 1,
		));
		$this->database->addFetchAllResult([
			[
				'id' => 42,
				'alias' => 'slide-1',
				'number' => 1,
				'filename' => 'file1.jpg',
				'filenameAlternative' => 'file1.webp',
				'title' => 'Slide 1',
				'speakerNotesTexy' => 'Slide 1',
			],
			[
				'id' => 43,
				'alias' => 'slide-2',
				'number' => 2,
				'filename' => 'file2.jpg',
				'filenameAlternative' => 'file2.webp',
				'title' => 'Slide 2',
				'speakerNotesTexy' => 'Slide 2',
			],
		]);
		$parameters = $this->templateParametersFactory->create('some-talk');
		Assert::same('messages.title.talk', $parameters->pageTitle->render());
		Assert::same('Talk <em>Title</em>', $parameters->pageHeader->render());
		Assert::same(303, $parameters->talk->getId());
		Assert::null($parameters->slideAlias);
		Assert::same('https://www.rizek.test/prednasky/some-talk', $parameters->canonicalLink);
		Assert::same('https://www.domain.example/i/images/talks/303/file1.jpg', $parameters->ogImage);
		Assert::same([], $parameters->upcomingTrainings);
		Assert::false($parameters->video->isLazyLoad());
		Assert::same(SlidesPlatform::SlideShare->getName(), $parameters->slidesPlatform);
	}


	public function testCreateWithSlide(): void
	{
		$this->database->addFetchResult($this->testDataFactory->getDatabaseResultData(
			id: 303,
			locale: $this->translator->getDefaultLocale(),
			action: 'some-talk',
			publishSlides: 1,
		));
		// Slides
		$this->database->addFetchAllResult([
			[
				'id' => 42,
				'alias' => 'slide-1',
				'number' => 1,
				'filename' => 'file1.jpg',
				'filenameAlternative' => 'file1.webp',
				'title' => 'Slide 1',
				'speakerNotesTexy' => 'Slide 1',
			],
			[
				'id' => 43,
				'alias' => 'slide-2',
				'number' => 2,
				'filename' => 'file2.jpg',
				'filenameAlternative' => 'file2.webp',
				'title' => 'Slide 2',
				'speakerNotesTexy' => 'Slide 2',
			],
			[
				'id' => 44,
				'alias' => 'slide-3',
				'number' => 3,
				'filename' => 'file3.jpg',
				'filenameAlternative' => 'file3.webp',
				'title' => 'Slide 3',
				'speakerNotesTexy' => 'Slide 3',
			],
			[
				'id' => 45,
				'alias' => 'slide-4',
				'number' => 4,
				'filename' => 'file4.jpg',
				'filenameAlternative' => 'file4.webp',
				'title' => 'Slide 4',
				'speakerNotesTexy' => 'Slide 4',
			],
		]);
		$parameters = $this->templateParametersFactory->create('some-talk', 'slide-2');
		Assert::same('https://www.rizek.test/prednasky/some-talk', $parameters->canonicalLink);
		Assert::same('https://www.domain.example/i/images/talks/303/file2.jpg', $parameters->ogImage);
		Assert::true($parameters->video->isLazyLoad());
		Assert::null($parameters->slidesPlatform);
	}


	public function testCreateWithSlideWithSlidesNotPublished(): void
	{
		$this->database->addFetchResult($this->testDataFactory->getDatabaseResultData(
			id: 303,
			locale: $this->translator->getDefaultLocale(),
			action: 'some-talk',
			publishSlides: 0,
		));
		// Slides
		$this->database->addFetchAllResult([
			[
				'id' => 42,
				'alias' => 'slide-1',
				'number' => 1,
				'filename' => 'file1.jpg',
				'filenameAlternative' => 'file1.webp',
				'title' => 'Slide 1',
				'speakerNotesTexy' => 'Slide 1',
			],
		]);
		Assert::exception(function (): void {
			$this->templateParametersFactory->create('some-talk', 'slide-1');
		}, TalkSlidesNotPublishedException::class, 'Slides not published for talk id 303');
	}


	public function testCreateWithUnknownSlide(): void
	{
		$this->database->addFetchResult($this->testDataFactory->getDatabaseResultData(
			id: 303,
			locale: $this->translator->getDefaultLocale(),
			action: 'some-talk',
			publishSlides: 1,
		));
		// Slides
		$this->database->addFetchAllResult([
			[
				'id' => 42,
				'alias' => 'slide-1',
				'number' => 1,
				'filename' => 'file1.jpg',
				'filenameAlternative' => 'file1.webp',
				'title' => 'Slide 1',
				'speakerNotesTexy' => 'Slide 1',
			],
		]);
		Assert::exception(function (): void {
			$this->templateParametersFactory->create('some-talk', 'slide-2');
		}, TalkSlideDoesNotExistException::class, "Talk id 303 doesn't have a slide 'slide-2'");
	}


	public function testCreateWithNumericSlide(): void
	{
		$this->database->addFetchResult($this->testDataFactory->getDatabaseResultData(
			id: 303,
			locale: $this->translator->getDefaultLocale(),
			action: 'some-talk',
			publishSlides: 1,
		));
		// Slides
		$this->database->addFetchAllResult([
			[
				'id' => 42,
				'alias' => 'slide-1',
				'number' => 1,
				'filename' => 'file1.jpg',
				'filenameAlternative' => 'file1.webp',
				'title' => 'Slide 1',
				'speakerNotesTexy' => 'Slide 1',
			],
			[
				'id' => 43,
				'alias' => 'slide-2',
				'number' => 2,
				'filename' => 'file2.jpg',
				'filenameAlternative' => 'file2.webp',
				'title' => 'Slide 2',
				'speakerNotesTexy' => 'Slide 2',
			],
		]);
		$e = Assert::exception(function (): void {
			$this->templateParametersFactory->create('some-talk', '2');
		}, IncorrectSlideAliasInUrlException::class);
		assert($e instanceof IncorrectSlideAliasInUrlException);
		Assert::same('slide-2', $e->correctAlias);
	}


	public function testCreateWithNumericSlideSlidesNotPublished(): void
	{
		$this->database->addFetchResult($this->testDataFactory->getDatabaseResultData(
			id: 303,
			locale: $this->translator->getDefaultLocale(),
			action: 'some-talk',
		));
		// Slides
		$this->database->addFetchAllResult([
			[
				'id' => 42,
				'alias' => 'slide-1',
				'number' => 1,
				'filename' => 'file1.jpg',
				'filenameAlternative' => 'file1.webp',
				'title' => 'Slide 1',
				'speakerNotesTexy' => 'Slide 1',
			],
		]);
		$e = Assert::exception(function (): void {
			$this->templateParametersFactory->create('some-talk', '1');
		}, IncorrectSlideAliasInUrlException::class);
		assert($e instanceof IncorrectSlideAliasInUrlException);
		Assert::null($e->correctAlias);
	}


	public function testCreateWithUnknownNumericSlide(): void
	{
		$this->database->addFetchResult($this->testDataFactory->getDatabaseResultData(
			id: 303,
			locale: $this->translator->getDefaultLocale(),
			action: 'some-talk',
			publishSlides: 1,
		));
		// Slides
		$this->database->addFetchAllResult([
			[
				'id' => 42,
				'alias' => 'slide-1',
				'number' => 1,
				'filename' => 'file1.jpg',
				'filenameAlternative' => 'file1.webp',
				'title' => 'Slide 1',
				'speakerNotesTexy' => 'Slide 1',
			],
		]);
		$e = Assert::exception(function (): void {
			$this->templateParametersFactory->create('some-talk', '2');
		}, IncorrectSlideAliasInUrlException::class);
		assert($e instanceof IncorrectSlideAliasInUrlException);
		Assert::null($e->correctAlias);
	}


	public function testCreateWithoutSlides(): void
	{
		$this->database->addFetchResult($this->testDataFactory->getDatabaseResultData(
			locale: $this->translator->getDefaultLocale(),
			action: 'some-talk',
			publishSlides: 0,
		));
		$parameters = $this->templateParametersFactory->create('some-talk');
		Assert::null($parameters->slides);
		Assert::same('https://www.rizek.test/prednasky/some-talk', $parameters->canonicalLink);
	}


	public function testCreateWithSlidesTalk(): void
	{
		$this->database->addFetchResult($this->testDataFactory->getDatabaseResultData(
			id: 303,
			locale: $this->translator->getDefaultLocale(),
			action: 'talk',
			slidesTalkId: 303,
			publishSlides: 0,
		));
		$this->database->addFetchResult($this->testDataFactory->getDatabaseResultData(
			id: 808,
			locale: $this->translator->getDefaultLocale(),
			action: 'slides-talk',
			publishSlides: 1,
		));
		// Slides
		$this->database->addFetchAllResult([
			[
				'id' => 42,
				'alias' => 'slide-1',
				'number' => 1,
				'filename' => 'file1.jpg',
				'filenameAlternative' => 'file1.webp',
				'title' => 'Slide 1',
				'speakerNotesTexy' => 'Slide 1',
			],
			[
				'id' => 43,
				'alias' => 'slide-2',
				'number' => 2,
				'filename' => 'file2.jpg',
				'filenameAlternative' => 'file2.webp',
				'title' => 'Slide 2',
				'speakerNotesTexy' => 'Slide 2',
			],
		]);
		$parameters = $this->templateParametersFactory->create('some-talk');
		Assert::same(303, $parameters->talk->getId());
		Assert::null($parameters->slideAlias);
		Assert::same('https://www.rizek.test/prednasky/slides-talk', $parameters->canonicalLink);
		Assert::same('https://www.domain.example/i/images/talks/808/file1.jpg', $parameters->ogImage);
		Assert::false($parameters->video->isLazyLoad());
	}


	public function testCreateWithSlidesTalkWithSlide(): void
	{
		$this->database->addFetchResult($this->testDataFactory->getDatabaseResultData(
			id: 303,
			locale: $this->translator->getDefaultLocale(),
			action: 'talk',
			slidesTalkId: 303,
			publishSlides: 0,
		));
		$this->database->addFetchResult($this->testDataFactory->getDatabaseResultData(
			id: 808,
			locale: $this->translator->getDefaultLocale(),
			action: 'slides-talk',
			publishSlides: 1,
		));
		// Slides
		$this->database->addFetchAllResult([
			[
				'id' => 42,
				'alias' => 'slide-1',
				'number' => 1,
				'filename' => 'file1.jpg',
				'filenameAlternative' => 'file1.webp',
				'title' => 'Slide 1',
				'speakerNotesTexy' => 'Slide 1',
			],
			[
				'id' => 43,
				'alias' => 'slide-2',
				'number' => 2,
				'filename' => 'file2.jpg',
				'filenameAlternative' => 'file2.webp',
				'title' => 'Slide 2',
				'speakerNotesTexy' => 'Slide 2',
			],
		]);
		$parameters = $this->templateParametersFactory->create('some-talk', 'slide-2');
		Assert::same('https://www.rizek.test/prednasky/slides-talk', $parameters->canonicalLink);
		Assert::same('https://www.domain.example/i/images/talks/808/file2.jpg', $parameters->ogImage);
		Assert::false($parameters->video->isLazyLoad());
	}


	public function testCreateWithSlidesTalkWithoutSlides(): void
	{
		$this->database->addFetchResult($this->testDataFactory->getDatabaseResultData(
			locale: $this->translator->getDefaultLocale(),
			action: 'talk',
			slidesTalkId: 303,
			publishSlides: 1,
		));
		$this->database->addFetchResult($this->testDataFactory->getDatabaseResultData(
			locale: $this->translator->getDefaultLocale(),
			action: 'slides-talk',
			publishSlides: 0,
		));
		$parameters = $this->templateParametersFactory->create('some-talk');
		Assert::null($parameters->slides);
		Assert::same('https://www.rizek.test/prednasky/slides-talk', $parameters->canonicalLink);
	}

}

TestCaseRunner::run(TalksTalkTemplateParametersFactoryTest::class);
