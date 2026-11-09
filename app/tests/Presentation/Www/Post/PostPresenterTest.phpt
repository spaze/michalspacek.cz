<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\Presentation\Www\Post;

use DateTime;
use MichalSpacekCz\Test\Application\ApplicationPresenter;
use MichalSpacekCz\Test\Application\LocaleLinkGeneratorMock;
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
final class PostPresenterTest extends TestCase
{

	private const string SLUG = 'clanek';
	private const string SWITCHER_LINK = 'https://www.burger.test/switcher';


	public function __construct(
		private readonly ApplicationPresenter $applicationPresenter,
		private readonly Database $database,
		private readonly LocaleLinkGeneratorMock $localeLinkGenerator,
		HttpRequestMock $httpRequest,
	) {
		$httpRequest->setMethod(IRequest::Get);
	}


	#[Override]
	protected function setUp(): void
	{
		$this->localeLinkGenerator->setLinks(['en_US' => self::SWITCHER_LINK]);
		$this->localeLinkGenerator->setLinksForLocaleParams([
			'cs_CZ' => 'https://www.rizek.test/clanek',
			'en_US' => 'https://www.burger.test/post',
		]);
	}


	#[Override]
	protected function tearDown(): void
	{
		$this->database->reset();
		$this->localeLinkGenerator->reset();
	}


	public function testDefaultTranslated(): void
	{
		$this->addPost(new DateTime('2025-01-01 00:00:00'));
		$html = $this->renderPost();
		Assert::contains('<link rel="alternate" hreflang="cs" href="https://www.rizek.test/clanek">', $html);
		Assert::contains('<link rel="alternate" hreflang="en" href="https://www.burger.test/post">', $html);
		Assert::contains('href="' . self::SWITCHER_LINK . '"', $html);
		Assert::same(['Www:Post:default'], $this->localeLinkGenerator->getLinksDestinations());
		Assert::same(['Www:Post:default'], $this->localeLinkGenerator->getLinksForLocaleParamsDestinations());
	}


	/**
	 * The switcher leads to the list of articles in the other language, which isn't this post in that language.
	 */
	public function testDefaultTranslationNotPublished(): void
	{
		$this->addPost(null);
		$html = $this->renderPost();
		Assert::notContains('hreflang', $html);
		Assert::contains('href="' . self::SWITCHER_LINK . '"', $html);
		Assert::same(['Www:Articles:'], $this->localeLinkGenerator->getLinksDestinations());
		Assert::same(['Www:Post:default'], $this->localeLinkGenerator->getLinksForLocaleParamsDestinations());
	}


	private function addPost(?DateTime $translationPublished): void
	{
		$this->database->setFetchDefaultResult([
			'id' => 1,
			'localeId' => 1,
			'translationGroupId' => 3,
			'locale' => 'cs_CZ',
			'slug' => self::SLUG,
			'titleTexy' => 'Článek',
			'leadTexy' => null,
			'textTexy' => 'Text',
			'published' => new DateTime('2025-01-01 00:00:00'),
			'previewKey' => null,
			'originallyTexy' => null,
			'ogImage' => null,
			'tags' => null,
			'slugTags' => null,
			'recommended' => null,
			'cspSnippets' => null,
			'allowedTags' => null,
			'omitExports' => null,
			'twitterCardId' => null,
			'twitterCard' => null,
			'twitterCardTitle' => null,
		]);
		$this->database->addFetchAllResult([]); // edits
		$this->database->addFetchAllResult([]); // upcoming trainings
		$this->database->addFetchAllResult([
			[
				'locale' => 'cs_CZ',
				'slug' => self::SLUG,
				'published' => new DateTime('2025-01-01 00:00:00'),
				'previewKey' => null,
				'slugTags' => null,
			],
			[
				'locale' => 'en_US',
				'slug' => 'post',
				'published' => $translationPublished,
				'previewKey' => $translationPublished === null ? 'key' : null,
				'slugTags' => null,
			],
		]);
	}


	private function renderPost(): string
	{
		$presenter = $this->applicationPresenter->createUiPresenter('Www:Post', 'Post', 'default');
		$presenter->autoCanonicalize = false;
		$response = $presenter->run(new Request('Www:Post', IRequest::Get, ['action' => 'default', 'slug' => self::SLUG]));
		assert($response instanceof TextResponse);
		$html = $response->getSource();
		assert(is_string($html) || $html instanceof Stringable);
		return (string)$html;
	}

}

TestCaseRunner::run(PostPresenterTest::class);
