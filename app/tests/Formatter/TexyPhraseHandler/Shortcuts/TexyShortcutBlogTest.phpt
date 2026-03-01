<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\Formatter\TexyPhraseHandler\Shortcuts;

use MichalSpacekCz\Test\Application\ApplicationPresenter;
use MichalSpacekCz\Test\Application\LocaleLinkGeneratorMock;
use MichalSpacekCz\Test\Database\Database;
use MichalSpacekCz\Test\Formatter\Exceptions\TexyFormatterTexyProcessLoopException;
use MichalSpacekCz\Test\Formatter\TexyFormatterMock;
use MichalSpacekCz\Test\TestCaseRunner;
use Nette\Application\Application;
use Nette\Application\UI\InvalidLinkException;
use Nette\Utils\JsonException;
use Override;
use Tester\Assert;
use Tester\TestCase;
use Texy\HandlerInvocation;
use Texy\HtmlElement;
use Texy\LineParser;
use Texy\Link;
use Texy\Modifier;

require __DIR__ . '/../../../bootstrap.php';

/** @testCase */
final class TexyShortcutBlogTest extends TestCase
{

	public function __construct(
		private readonly TexyShortcutBlog $shortcutBlog,
		private readonly TexyFormatterMock $texyFormatter,
		private readonly Database $database,
		private readonly LocaleLinkGeneratorMock $localeLinkGenerator,
		ApplicationPresenter $applicationPresenter,
		Application $application,
	) {
		$applicationPresenter->setLinkCallback($application, fn() => '');
	}


	#[Override]
	protected function setUp(): void
	{
		$this->texyFormatter->willThrow(new TexyFormatterTexyProcessLoopException());
		$this->localeLinkGenerator->setAllLinks(['cs_CZ' => 'https://cz.example/']);
		$this->database->setFetchAllDefaultResult([
			[
				'locale' => 'cs_CZ',
				'slug' => 'foo',
				'published' => null,
				'previewKey' => null,
				'slugTags' => null,
			],
		]);
	}


	#[Override]
	protected function tearDown(): void
	{
		$this->database->reset();
	}


	public function testCanResolve(): void
	{
		Assert::true($this->shortcutBlog->canResolve('blog:'));
		Assert::false($this->shortcutBlog->canResolve('Blog:'));
		Assert::false($this->shortcutBlog->canResolve('foo:'));
	}


	public function testResolve(): void
	{
		$link = new Link('');
		$this->resolve('blog:foo', $link);
		Assert::same('https://cz.example/', $link->URL);
	}


	public function testResolveNoLink(): void
	{
		Assert::exception(function (): void {
			$this->resolve('blog:', new Link(''));
		}, InvalidLinkException::class, 'No link specified in [blog:]');
	}


	public function testResolveUnableToGenerateLink(): void
	{
		$this->localeLinkGenerator->setAllLinks(['pt_BR' => 'https://pt.example/']);
		Assert::exception(function (): void {
			$this->resolve('blog:fred', new Link(''));
		}, InvalidLinkException::class, 'Unable to generate link to Www:Post:default for locale cs_CZ with params {"cs_CZ":{"slug":"foo","preview":null},"*":{"slug":"foo","preview":null}}');
	}


	public function testResolveBlogDoesNotExist(): void
	{
		$this->database->reset();
		Assert::exception(function (): void {
			$this->resolve('blog:fred', new Link(''));
		}, InvalidLinkException::class, "Blog post linked in [blog:fred] doesn't exist");
	}


	public function testResolveBlogDoesNotExistInLocale(): void
	{
		$this->database->setFetchAllDefaultResult([
			[
				'locale' => 'pt_BR',
				'slug' => 'foo',
				'published' => null,
				'previewKey' => null,
				'slugTags' => null,
			],
		]);
		Assert::exception(function (): void {
			$this->resolve('blog:fred', new Link(''));
		}, InvalidLinkException::class, "Blog post linked in [blog:fred] doesn't exist in locale cs_CZ");
	}


	public function testResolveInvalidBlogJson(): void
	{
		$this->database->setFetchAllDefaultResult([
			[
				'locale' => 'cs_CZ',
				'slug' => 'foo',
				'published' => null,
				'previewKey' => null,
				'slugTags' => '{broken json',
			],
		]);
		Assert::exception(function (): void {
			$this->resolve('blog:fred', new Link(''));
		}, JsonException::class, 'Syntax error');
	}


	private function resolve(string $url, Link $link): void
	{
		$texy = $this->texyFormatter->getTexy();
		$texy->process('');
		$result = $this->shortcutBlog->resolve(
			$url,
			new HandlerInvocation([fn() => 'Link'], new LineParser($texy, new HtmlElement()), []),
			'',
			'',
			new Modifier(''),
			$link,
		);
		Assert::null($result);
	}

}

TestCaseRunner::run(TexyShortcutBlogTest::class);
