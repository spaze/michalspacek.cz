<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\Presentation\Www\Tags;

use DateTime;
use MichalSpacekCz\Tags\Tags;
use MichalSpacekCz\Test\Application\ApplicationPresenter;
use MichalSpacekCz\Test\Application\LocaleLinkGeneratorMock;
use MichalSpacekCz\Test\Articles\ArticlesMock;
use MichalSpacekCz\Test\Database\Database;
use MichalSpacekCz\Test\Http\Request as HttpRequestMock;
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
final class TagsPresenterTest extends TestCase
{

	private const string TAG = 'neco';
	private const string SWITCHER_LINK = 'https://www.burger.test/switcher';


	public function __construct(
		private readonly ApplicationPresenter $applicationPresenter,
		private readonly ArticlesMock $articles,
		private readonly Database $database,
		private readonly LocaleLinkGeneratorMock $localeLinkGenerator,
		private readonly Tags $tags,
		HttpRequestMock $httpRequest,
	) {
		$httpRequest->setMethod(IRequest::Get);
	}


	#[Override]
	protected function setUp(): void
	{
		$this->articles->setAllTags([self::TAG => 'Něco']);
		$this->localeLinkGenerator->setLinks(['en_US' => self::SWITCHER_LINK]);
		$this->localeLinkGenerator->setLinksForLocaleParams([
			'cs_CZ' => 'https://www.rizek.test/stitky/neco',
			'en_US' => 'https://www.burger.test/tags/something',
		]);
	}


	#[Override]
	protected function tearDown(): void
	{
		$this->articles->reset();
		$this->database->reset();
		$this->localeLinkGenerator->reset();
	}


	public function testDefault(): void
	{
		$presenter = $this->applicationPresenter->createUiPresenter('Www:Tags', 'Tags', 'default');
		$presenter->autoCanonicalize = false;
		$response = $presenter->run(new Request('Www:Tags', IRequest::Get, ['action' => 'default']));
		assert($response instanceof TextResponse);
		$html = $response->getSource();
		assert(is_string($html) || $html instanceof Stringable);
		Assert::contains('<link rel="alternate" hreflang="cs" href="https://www.rizek.test/stitky/neco">', (string)$html);
		Assert::contains('<link rel="alternate" hreflang="en" href="https://www.burger.test/tags/something">', (string)$html);
		Assert::same(['Www:Tags:default'], $this->localeLinkGenerator->getLinksDestinations());
		Assert::same(['Www:Tags:default'], $this->localeLinkGenerator->getLinksForLocaleParamsDestinations());
	}


	public function testTagTranslated(): void
	{
		$this->addPostWithTranslation(new DateTime('2025-01-01 00:00:00'));
		$html = $this->renderTag();
		Assert::contains('<link rel="alternate" hreflang="cs" href="https://www.rizek.test/stitky/neco">', $html);
		Assert::contains('<link rel="alternate" hreflang="en" href="https://www.burger.test/tags/something">', $html);
		Assert::contains('href="' . self::SWITCHER_LINK . '"', $html);
		Assert::same(['Www:Tags:tag'], $this->localeLinkGenerator->getLinksDestinations());
		Assert::same(['Www:Tags:tag'], $this->localeLinkGenerator->getLinksForLocaleParamsDestinations());
	}


	/**
	 * The switcher leads to the list of tags in the other language, which isn't this page in that language.
	 */
	public function testTagTranslationNotPublished(): void
	{
		$this->addPostWithTranslation(null);
		$html = $this->renderTag();
		Assert::notContains('hreflang', $html);
		Assert::contains('href="' . self::SWITCHER_LINK . '"', $html);
		Assert::same(['Www:Tags:'], $this->localeLinkGenerator->getLinksDestinations());
		Assert::same(['Www:Tags:tag'], $this->localeLinkGenerator->getLinksForLocaleParamsDestinations());
	}


	public function testTagNotTranslated(): void
	{
		$html = $this->renderTag();
		Assert::notContains('hreflang', $html);
		Assert::contains('href="' . self::SWITCHER_LINK . '"', $html);
		Assert::same(['Www:Tags:'], $this->localeLinkGenerator->getLinksDestinations());
	}


	private function addPostWithTranslation(?DateTime $translationPublished): void
	{
		$this->articles->addBlogPost(1, new DateTime('2025-01-01 00:00:00'), 'post', slugTags: [self::TAG]);
		$this->database->addFetchAllResult([
			[
				'translationGroupId' => 1,
				'locale' => 'cs_CZ',
				'published' => new DateTime('2025-01-01 00:00:00'),
				'slugTags' => $this->tags->serialize([self::TAG]),
			],
			[
				'translationGroupId' => 1,
				'locale' => 'en_US',
				'published' => $translationPublished,
				'slugTags' => $this->tags->serialize(['something']),
			],
		]);
	}


	private function renderTag(): string
	{
		$presenter = $this->applicationPresenter->createUiPresenter('Www:Tags', 'Tags', 'tag');
		$presenter->autoCanonicalize = false;
		$response = $presenter->run(new Request('Www:Tags', IRequest::Get, ['action' => 'tag', 'tag' => self::TAG]));
		assert($response instanceof TextResponse);
		$html = $response->getSource();
		assert(is_string($html) || $html instanceof Stringable);
		return (string)$html;
	}

}

TestCaseRunner::run(TagsPresenterTest::class);
