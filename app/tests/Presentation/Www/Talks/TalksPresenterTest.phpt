<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\Presentation\Www\Talks;

use MichalSpacekCz\Test\Application\ApplicationPresenter;
use MichalSpacekCz\Test\Application\LocaleLinkGeneratorMock;
use MichalSpacekCz\Test\Database\Database;
use MichalSpacekCz\Test\Http\Request as HttpRequestMock;
use MichalSpacekCz\Test\Talks\TalkTestDataFactory;
use MichalSpacekCz\Test\TestCaseRunner;
use Nette\Application\Request;
use Nette\Application\Responses\TextResponse;
use Nette\Http\IRequest;
use Override;
use Stringable;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../../../bootstrap.php';

/** @testCase */
final class TalksPresenterTest extends TestCase
{

	private const string NAME = 'prednaska';
	private const string SWITCHER_LINK = 'https://www.burger.test/switcher';

	private const array TRANSLATIONS = [
		'cs_CZ' => ['name' => self::NAME],
		'en_US' => ['name' => 'talk'],
	];


	public function __construct(
		private readonly ApplicationPresenter $applicationPresenter,
		private readonly Database $database,
		private readonly LocaleLinkGeneratorMock $localeLinkGenerator,
		private readonly TalkTestDataFactory $talkTestDataFactory,
		HttpRequestMock $httpRequest,
	) {
		$httpRequest->setMethod(IRequest::Get);
	}


	#[Override]
	protected function setUp(): void
	{
		$this->localeLinkGenerator->setLinks(['en_US' => self::SWITCHER_LINK]);
		$this->localeLinkGenerator->setLinksForLocaleParams([
			'cs_CZ' => 'https://www.rizek.test/prednasky/prednaska',
			'en_US' => 'https://www.burger.test/talks/talk',
		]);
	}


	#[Override]
	protected function tearDown(): void
	{
		$this->database->reset();
		$this->localeLinkGenerator->reset();
	}


	public function testDefault(): void
	{
		$presenter = $this->applicationPresenter->createUiPresenter('Www:Talks', 'Talks', 'default');
		$presenter->autoCanonicalize = false;
		$response = $presenter->run(new Request('Www:Talks', IRequest::Get, ['action' => 'default']));
		assert($response instanceof TextResponse);
		$html = $response->getSource();
		assert(is_string($html) || $html instanceof Stringable);
		Assert::contains('<link rel="alternate" hreflang="cs" href="https://www.rizek.test/prednasky/prednaska">', (string)$html);
		Assert::contains('<link rel="alternate" hreflang="en" href="https://www.burger.test/talks/talk">', (string)$html);
		Assert::same(['Www:Talks:default'], $this->localeLinkGenerator->getLinksDestinations());
		Assert::same(['Www:Talks:default'], $this->localeLinkGenerator->getLinksForLocaleParamsDestinations());
	}


	public function testTalkTranslated(): void
	{
		$this->database->setFetchDefaultResult($this->talkTestDataFactory->getDatabaseResultData(locale: 'cs_CZ', translationGroupId: 7, action: self::NAME));
		$this->database->addFetchPairsResult(['cs_CZ' => self::NAME, 'en_US' => 'talk']);
		$this->database->addFetchPairsResult(['cs_CZ' => self::NAME, 'en_US' => 'talk']);
		$html = $this->renderTalk();
		Assert::contains('<link rel="alternate" hreflang="cs" href="https://www.rizek.test/prednasky/prednaska">', $html);
		Assert::contains('<link rel="alternate" hreflang="en" href="https://www.burger.test/talks/talk">', $html);
		Assert::contains('href="' . self::SWITCHER_LINK . '"', $html);
		Assert::same(['Www:Talks:talk'], $this->localeLinkGenerator->getLinksDestinations());
		Assert::same([self::TRANSLATIONS], $this->localeLinkGenerator->getLinksParams());
		Assert::same(['Www:Talks:talk'], $this->localeLinkGenerator->getLinksForLocaleParamsDestinations());
	}


	/**
	 * The translation names the talk whose slides it shows as canonical, so it's not this talk's alternate, but the
	 * switcher still leads to it.
	 */
	public function testTalkTranslationShowsOtherTalksSlides(): void
	{
		$this->database->setFetchDefaultResult($this->talkTestDataFactory->getDatabaseResultData(locale: 'cs_CZ', translationGroupId: 7, action: self::NAME));
		$this->database->addFetchPairsResult(['cs_CZ' => self::NAME, 'en_US' => 'talk']);
		$this->database->addFetchPairsResult(['cs_CZ' => self::NAME]);
		$html = $this->renderTalk();
		Assert::notContains('hreflang', $html);
		Assert::contains('href="' . self::SWITCHER_LINK . '"', $html);
		Assert::same(['Www:Talks:talk'], $this->localeLinkGenerator->getLinksDestinations());
		Assert::same([self::TRANSLATIONS], $this->localeLinkGenerator->getLinksParams());
		Assert::same(['Www:Talks:talk'], $this->localeLinkGenerator->getLinksForLocaleParamsDestinations());
	}


	/**
	 * The talk names the one whose slides it shows as canonical, so it has no alternates, but the switcher still leads to
	 * its translation.
	 */
	public function testTalkShowsOtherTalksSlides(): void
	{
		$this->database->setFetchDefaultResult($this->talkTestDataFactory->getDatabaseResultData(locale: 'cs_CZ', translationGroupId: 7, action: self::NAME, slidesTalkId: 303));
		$this->database->addFetchPairsResult(['cs_CZ' => self::NAME, 'en_US' => 'talk']);
		// Only used if the alternates were wrongly looked up for this talk
		$this->database->addFetchPairsResult(['cs_CZ' => self::NAME, 'en_US' => 'talk']);
		$html = $this->renderTalk();
		Assert::notContains('hreflang', $html);
		Assert::contains('href="' . self::SWITCHER_LINK . '"', $html);
		Assert::same(['Www:Talks:talk'], $this->localeLinkGenerator->getLinksDestinations());
		Assert::same([self::TRANSLATIONS], $this->localeLinkGenerator->getLinksParams());
	}


	/**
	 * The switcher leads to the list of talks in the other language, which isn't this talk in that language.
	 */
	public function testTalkNotTranslated(): void
	{
		$this->database->setFetchDefaultResult($this->talkTestDataFactory->getDatabaseResultData(locale: 'cs_CZ', action: self::NAME));
		$html = $this->renderTalk();
		Assert::notContains('hreflang', $html);
		Assert::contains('href="' . self::SWITCHER_LINK . '"', $html);
		Assert::same(['Www:Talks:'], $this->localeLinkGenerator->getLinksDestinations());
		Assert::same(['Www:Talks:talk'], $this->localeLinkGenerator->getLinksForLocaleParamsDestinations());
	}


	/**
	 * A slide's page names the talk's page as canonical, so it has no alternates, but the switcher still leads to the
	 * talk's translation.
	 */
	public function testTalkSlide(): void
	{
		$this->database->setFetchDefaultResult($this->talkTestDataFactory->getDatabaseResultData(locale: 'cs_CZ', translationGroupId: 7, action: self::NAME, publishSlides: 1));
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
		$this->database->addFetchPairsResult(['cs_CZ' => self::NAME, 'en_US' => 'talk']);
		// Only used if the alternates were wrongly looked up for a slide's page
		$this->database->addFetchPairsResult(['cs_CZ' => self::NAME, 'en_US' => 'talk']);
		$html = $this->renderTalk('slide-1');
		Assert::contains('<link rel="canonical" href="https://www.rizek.test/prednasky/prednaska">', $html);
		Assert::notContains('hreflang', $html);
		Assert::contains('href="' . self::SWITCHER_LINK . '"', $html);
		Assert::same(['Www:Talks:talk'], $this->localeLinkGenerator->getLinksDestinations());
		Assert::same([self::TRANSLATIONS], $this->localeLinkGenerator->getLinksParams());
	}


	private function renderTalk(?string $slide = null): string
	{
		$presenter = $this->applicationPresenter->createUiPresenter('Www:Talks', 'Talks', 'talk');
		$presenter->autoCanonicalize = false;
		$response = $presenter->run(new Request('Www:Talks', IRequest::Get, ['action' => 'talk', 'name' => self::NAME, 'slide' => $slide]));
		assert($response instanceof TextResponse);
		$html = $response->getSource();
		assert(is_string($html) || $html instanceof Stringable);
		return (string)$html;
	}

}

TestCaseRunner::run(TalksPresenterTest::class);
