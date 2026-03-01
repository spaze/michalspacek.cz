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
final class TexyShortcutBlogWithLocaleTest extends TestCase
{

	public function __construct(
		private readonly TexyShortcutBlogWithLocale $shortcutBlogWithLocale,
		private readonly TexyFormatterMock $texyFormatter,
		private readonly Database $database,
		LocaleLinkGeneratorMock $localeLinkGenerator,
		ApplicationPresenter $applicationPresenter,
		Application $application,
	) {
		$applicationPresenter->setLinkCallback($application, null);
		$localeLinkGenerator->setAllLinks(['cs_CZ' => 'https://cz.example/']);
	}


	#[Override]
	protected function setUp(): void
	{
		$this->texyFormatter->willThrow(new TexyFormatterTexyProcessLoopException());
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
		Assert::true($this->shortcutBlogWithLocale->canResolve('blog-cs_CZ:'));
		Assert::false($this->shortcutBlogWithLocale->canResolve('Blog-cs_CZ:'));
		Assert::false($this->shortcutBlogWithLocale->canResolve('blog:'));
		Assert::false($this->shortcutBlogWithLocale->canResolve('Blog:'));
		Assert::false($this->shortcutBlogWithLocale->canResolve('foo:'));
	}


	public function testResolve(): void
	{
		$link = new Link('');
		$this->resolve('blog-cs_CZ:foo', $link);
		Assert::same('https://cz.example/', $link->URL);
	}


	public function testResolveNoLink(): void
	{
		Assert::exception(function (): void {
			$this->resolve('blog-cs_CZ:', new Link(''));
		}, InvalidLinkException::class, 'No link specified in [blog-cs_CZ:]');
	}


	public function testResolveBlogDoesNotExist(): void
	{
		$this->database->reset();
		Assert::exception(function (): void {
			$this->resolve('blog-cs_CZ:fred', new Link(''));
		}, InvalidLinkException::class, "Blog post linked in [blog-cs_CZ:fred] doesn't exist");
	}


	public function testResolveBlogDoesNotExistInLocale(): void
	{
		Assert::exception(function (): void {
			$this->resolve('blog-pt_BR:fred', new Link(''));
		}, InvalidLinkException::class, "Blog post linked in [blog-pt_BR:fred] doesn't exist in locale pt_BR");
	}


	private function resolve(string $url, Link $link): void
	{
		$texy = $this->texyFormatter->getTexy();
		$texy->process('');
		$result = $this->shortcutBlogWithLocale->resolve(
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

TestCaseRunner::run(TexyShortcutBlogWithLocaleTest::class);
