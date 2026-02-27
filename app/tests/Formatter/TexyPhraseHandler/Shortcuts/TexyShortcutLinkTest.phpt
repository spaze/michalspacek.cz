<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\Formatter\TexyPhraseHandler\Shortcuts;

use MichalSpacekCz\Formatter\TexyFormatter;
use MichalSpacekCz\Test\Application\ApplicationPresenter;
use MichalSpacekCz\Test\Application\LocaleLinkGeneratorMock;
use MichalSpacekCz\Test\Database\Database;
use MichalSpacekCz\Test\TestCaseRunner;
use MichalSpacekCz\Training\Trainings\Trainings;
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
final class TexyShortcutLinkTest extends TestCase
{

	public function __construct(
		private readonly TexyShortcutLink $shortcutLink,
		private readonly TexyFormatter $texyFormatter,
		private readonly Database $database,
		LocaleLinkGeneratorMock $localeLinkGenerator,
		ApplicationPresenter $applicationPresenter,
		Application $application,
	) {
		$applicationPresenter->setLinkCallback($application, fn() => '');
		$localeLinkGenerator->setAllLinks(['cs_CZ' => 'https://com.example/']);
	}


	#[Override]
	protected function tearDown(): void
	{
		$this->database->reset();
	}


	public function testCanResolve(): void
	{
		Assert::true($this->shortcutLink->canResolve('link:'));
		Assert::false($this->shortcutLink->canResolve('Link:'));
		Assert::false($this->shortcutLink->canResolve('foo:'));
	}


	public function testResolve(): void
	{
		$link = new Link('');
		$this->resolve('link:Foo:bar', $link);
		Assert::same('https://com.example/', $link->URL);
	}


	public function testResolveNoLink(): void
	{
		Assert::exception(function (): void {
			$this->resolve('link:', new Link(''));
		}, InvalidLinkException::class, 'No link specified in [link:]');
	}


	public function testResolveInvalidTrainingLink(): void
	{
		Assert::exception(function (): void {
			$this->resolve('link:' . Trainings::TRAINING_ACTION, new Link(''));
		}, InvalidLinkException::class, 'No training specified in [link:Www:Trainings:training]');
	}


	public function testResolveMissingTrainingLink(): void
	{
		Assert::exception(function (): void {
			$this->resolve('link:' . Trainings::TRAINING_ACTION . ' foo', new Link(''));
		}, InvalidLinkException::class, "Training linked in [link:Www:Trainings:training foo] doesn't exist");
	}


	public function testResolveTrainingNoLocaleLink(): void
	{
		$this->database->setFetchPairsDefaultResult(['cs_BŽ' => 'něco']);
		Assert::exception(function (): void {
			$this->resolve('link:' . Trainings::TRAINING_ACTION . ' foo', new Link(''));
		}, InvalidLinkException::class, "Training linked in [link:Www:Trainings:training foo] doesn't exist in locale cs_CZ");
	}


	private function resolve(string $url, Link $link): void
	{
		$texy = $this->texyFormatter->getTexy();
		$texy->process('');
		$result = $this->shortcutLink->resolve(
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

TestCaseRunner::run(TexyShortcutLinkTest::class);
