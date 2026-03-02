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
final class TexyShortcutTalkTest extends TestCase
{

	public function __construct(
		private readonly TexyShortcutTalk $shortcutTalk,
		private readonly TexyFormatterMock $texyFormatter,
		private readonly Database $database,
		LocaleLinkGeneratorMock $localeLinkGenerator,
		ApplicationPresenter $applicationPresenter,
		Application $application,
	) {
		$applicationPresenter->setLinkCallback($application, fn(string $destination, array $args) => $destination . ' ' . implode(',', $args));
		$localeLinkGenerator->setAllLinks(['cs_CZ' => 'https://com.example/']);
	}


	#[Override]
	protected function setUp(): void
	{
		$this->texyFormatter->willThrow(new TexyFormatterTexyProcessLoopException());
		// Talk metadata
		$this->database->setFetchDefaultResult([
			'id' => 42,
			'slidesTalkId' => null,
			'publishSlides' => 1,
		]);
		// Slide exists
		$this->database->setFetchFieldDefaultResult(1);
	}


	#[Override]
	protected function tearDown(): void
	{
		$this->database->reset();
	}


	public function testCanResolve(): void
	{
		Assert::true($this->shortcutTalk->canResolve('talk:'));
		Assert::false($this->shortcutTalk->canResolve('Talk:'));
		Assert::false($this->shortcutTalk->canResolve('foo:'));
	}


	public function testResolve(): void
	{
		$link = new Link('');
		$this->resolve('talk:foo', $link);
		Assert::same('//:Www:Talks:talk foo', $link->URL);

		$this->resolve('talk:foo#slide-name', $link);
		Assert::same('//:Www:Talks:talk foo,slide-name', $link->URL);
	}


	public function testResolveNoTalk(): void
	{
		$this->database->reset();
		Assert::exception(function (): void {
			$this->resolve('talk:foo', new Link(''));
		}, InvalidLinkException::class, "Talk specified in [talk:foo] doesn't exist");
	}


	public function testResolveTalkSlidesNotPublished(): void
	{
		$this->database->setFetchDefaultResult([
			'id' => 42,
			'slidesTalkId' => null,
			'publishSlides' => 0,
		]);
		Assert::exception(function (): void {
			$this->resolve('talk:foo#bar', new Link(''));
		}, InvalidLinkException::class, 'Slides are not published for the talk specified in [talk:foo#bar]');
	}


	public function testResolveSlideTalkSlidesNotPublished(): void
	{
		$this->database->setFetchDefaultResult([
			'id' => 42,
			'slidesTalkId' => 24,
			'publishSlides' => 0,
		]);
		Assert::exception(function (): void {
			$this->resolve('talk:foo#bar', new Link(''));
		}, InvalidLinkException::class, 'Slides are not published for the slide talk for the talk specified in [talk:foo#bar]');
	}


	public function testResolveTalkSlidesNotPublishedButNotSlideLink(): void
	{
		$this->database->setFetchDefaultResult([
			'id' => 42,
			'slidesTalkId' => 24,
			'publishSlides' => 0,
		]);
		$link = new Link('');
		$this->resolve('talk:foo', $link);
		Assert::same('//:Www:Talks:talk foo', $link->URL);
	}


	public function testResolveNoSlidesTalk(): void
	{
		$this->database->reset();
		$this->database->addFetchResult([
			'id' => 42,
			'slidesTalkId' => 24,
			'publishSlides' => 1,
		]);
		Assert::exception(function (): void {
			$this->resolve('talk:foo#bar', new Link(''));
		}, InvalidLinkException::class, "Slides talk for the talk specified in [talk:foo#bar] doesn't exist");
	}


	public function testResolveSlidesTalkButNoSlide(): void
	{
		$this->database->reset();
		$this->database->addFetchResult([
			'id' => 42,
			'slidesTalkId' => 24,
			'publishSlides' => 1,
		]);
		$this->database->addFetchResult([
			'id' => 24,
			'slidesTalkId' => null,
			'publishSlides' => 1,
		]);
		Assert::exception(function (): void {
			$this->resolve('talk:foo#bar', new Link(''));
		}, InvalidLinkException::class, "The slide linked in [talk:foo#bar] doesn't exist, only the slides talk does");
	}


	public function testResolveSlidesTalk(): void
	{
		$this->database->reset();
		$this->database->addFetchResult([
			'id' => 42,
			'slidesTalkId' => 24,
			'publishSlides' => 1,
		]);
		$this->database->addFetchResult([
			'id' => 24,
			'slidesTalkId' => null,
			'publishSlides' => 0,
		]);
		$this->database->setFetchFieldDefaultResult(1);
		$link = new Link('');
		$this->resolve('talk:foo', $link);
		Assert::same('//:Www:Talks:talk foo', $link->URL);
	}


	public function testResolveTalkButNoSlide(): void
	{
		$this->database->setFetchFieldDefaultResult(null);
		Assert::exception(function (): void {
			$this->resolve('talk:foo#slide', new Link(''));
		}, InvalidLinkException::class, "The slide linked in [talk:foo#slide] doesn't exist, only the talk does");
	}


	public function testResolveNoLink(): void
	{
		Assert::exception(function (): void {
			$this->resolve('talk:', new Link(''));
		}, InvalidLinkException::class, 'No talk specified in [talk:]');
	}


	private function resolve(string $url, Link $link): void
	{
		$texy = $this->texyFormatter->getTexy();
		$texy->process('');
		$result = $this->shortcutTalk->resolve(
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

TestCaseRunner::run(TexyShortcutTalkTest::class);
