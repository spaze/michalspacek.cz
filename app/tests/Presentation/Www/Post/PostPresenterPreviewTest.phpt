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

/**
 * Apart from PostPresenterTest, because the post loader keeps the first post it loads, and this one is a different post.
 *
 * @testCase
 */
final class PostPresenterPreviewTest extends TestCase
{

	private const string SLUG = 'clanek';
	private const string PREVIEW_KEY = 'key';
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
	protected function tearDown(): void
	{
		$this->database->reset();
		$this->localeLinkGenerator->reset();
	}


	/**
	 * The post isn't out yet, so its address isn't this post in any language until it is.
	 */
	public function testDefaultPreview(): void
	{
		$this->localeLinkGenerator->setLinks(['en_US' => self::SWITCHER_LINK]);
		$this->localeLinkGenerator->setLinksForLocaleParams([
			'cs_CZ' => 'https://www.rizek.test/clanek',
			'en_US' => 'https://www.burger.test/post',
		]);
		$this->database->setFetchDefaultResult([
			'id' => 1,
			'localeId' => 1,
			'translationGroupId' => 3,
			'locale' => 'cs_CZ',
			'slug' => self::SLUG,
			'titleTexy' => 'Článek',
			'leadTexy' => null,
			'textTexy' => 'Text',
			'published' => new DateTime('+1 day'),
			'previewKey' => self::PREVIEW_KEY,
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
				'published' => new DateTime('+1 day'),
				'previewKey' => self::PREVIEW_KEY,
				'slugTags' => null,
			],
			[
				'locale' => 'en_US',
				'slug' => 'post',
				'published' => new DateTime('2025-01-01 00:00:00'),
				'previewKey' => null,
				'slugTags' => null,
			],
		]);
		$presenter = $this->applicationPresenter->createUiPresenter('Www:Post', 'Post', 'default');
		$presenter->autoCanonicalize = false;
		$response = $presenter->run(new Request('Www:Post', IRequest::Get, ['action' => 'default', 'slug' => self::SLUG, 'preview' => self::PREVIEW_KEY]));
		assert($response instanceof TextResponse);
		$source = $response->getSource();
		assert(is_string($source) || $source instanceof Stringable);
		$html = (string)$source;
		Assert::notContains('hreflang', $html);
		Assert::contains('href="' . self::SWITCHER_LINK . '"', $html);
		Assert::same(['Www:Post:default'], $this->localeLinkGenerator->getLinksDestinations());
	}

}

TestCaseRunner::run(PostPresenterPreviewTest::class);
