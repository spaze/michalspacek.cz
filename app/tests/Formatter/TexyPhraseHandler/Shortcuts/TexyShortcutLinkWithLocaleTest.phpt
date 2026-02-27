<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\Formatter\TexyPhraseHandler\Shortcuts;

use MichalSpacekCz\Formatter\TexyFormatter;
use MichalSpacekCz\Test\Application\ApplicationPresenter;
use MichalSpacekCz\Test\Application\LocaleLinkGeneratorMock;
use MichalSpacekCz\Test\TestCaseRunner;
use MichalSpacekCz\Training\Trainings\Trainings;
use Nette\Application\Application;
use Nette\Application\UI\InvalidLinkException;
use Tester\Assert;
use Tester\TestCase;
use Texy\HandlerInvocation;
use Texy\HtmlElement;
use Texy\LineParser;
use Texy\Link;
use Texy\Modifier;

require __DIR__ . '/../../../bootstrap.php';

/** @testCase */
final class TexyShortcutLinkWithLocaleTest extends TestCase
{

	public function __construct(
		private readonly TexyShortcutLinkWithLocale $shortcutLinkWithLocale,
		private readonly TexyFormatter $texyFormatter,
		LocaleLinkGeneratorMock $localeLinkGenerator,
		ApplicationPresenter $applicationPresenter,
		Application $application,
	) {
		$applicationPresenter->setLinkCallback($application, fn() => '');
		$localeLinkGenerator->setAllLinks(['cs_CZ' => 'https://cz.example/']);
	}


	public function testCanResolve(): void
	{
		Assert::true($this->shortcutLinkWithLocale->canResolve('link-cs_CZ:'));
		Assert::false($this->shortcutLinkWithLocale->canResolve('Link-cs_CZ:'));
		Assert::false($this->shortcutLinkWithLocale->canResolve('link:'));
		Assert::false($this->shortcutLinkWithLocale->canResolve('Link:'));
		Assert::false($this->shortcutLinkWithLocale->canResolve('foo:'));
	}


	public function testResolve(): void
	{
		$link = new Link('');
		$this->resolve('link-cs_CZ:Foo:bar', $link);
		Assert::same('https://cz.example/', $link->URL);
	}


	public function testResolveNoLink(): void
	{
		Assert::exception(function (): void {
			$this->resolve('link-cs_CZ:', new Link(''));
		}, InvalidLinkException::class, 'No link specified in [link-cs_CZ:]');
	}


	public function testResolveInvalidLink(): void
	{
		Assert::exception(function (): void {
			$this->resolve('link-pt_BR:foo bar', new Link(''));
		}, InvalidLinkException::class, 'Unable to generate link to foo for locale pt_BR with params {"pt_BR":["bar"],"*":["bar"]}');
	}


	public function testResolveInvalidTrainingLink(): void
	{
		Assert::exception(function (): void {
			$this->resolve('link-cs_CZ:' . Trainings::TRAINING_ACTION, new Link(''));
		}, InvalidLinkException::class, 'No training specified in [link-cs_CZ:Www:Trainings:training]');
	}


	private function resolve(string $url, Link $link): void
	{
		$texy = $this->texyFormatter->getTexy();
		$texy->process('');
		$result = $this->shortcutLinkWithLocale->resolve(
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

TestCaseRunner::run(TexyShortcutLinkWithLocaleTest::class);
