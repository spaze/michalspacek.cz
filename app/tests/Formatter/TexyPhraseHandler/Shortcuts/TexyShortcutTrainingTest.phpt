<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\Formatter\TexyPhraseHandler\Shortcuts;

use MichalSpacekCz\Test\Application\ApplicationPresenter;
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
final class TexyShortcutTrainingTest extends TestCase
{

	public function __construct(
		private readonly TexyShortcutTraining $shortcutTraining,
		private readonly TexyFormatterMock $texyFormatter,
		private readonly Database $database,
		ApplicationPresenter $applicationPresenter,
		Application $application,
	) {
		$applicationPresenter->setLinkCallback($application, null);
	}


	#[Override]
	protected function setUp(): void
	{
		$this->texyFormatter->willThrow(new TexyFormatterTexyProcessLoopException());
		$this->database->setFetchPairsDefaultResult(['cs_CZ' => 'něco']);
	}


	#[Override]
	protected function tearDown(): void
	{
		$this->database->reset();
	}


	public function testCanResolve(): void
	{
		Assert::true($this->shortcutTraining->canResolve('training:'));
		Assert::false($this->shortcutTraining->canResolve('Training:'));
		Assert::false($this->shortcutTraining->canResolve('foo:'));
	}


	public function testResolve(): void
	{
		Assert::same('Školení <small>(**TRAINING_DATE:něco**)</small>', $this->resolve('training:something'));
	}


	public function testResolveNoLink(): void
	{
		Assert::exception(function (): void {
			$this->resolve('training:');
		}, InvalidLinkException::class, 'No training specified in [training:]');
	}


	public function testResolveNoTraining(): void
	{
		$this->database->reset();
		Assert::exception(function (): void {
			$this->resolve('training:foo');
		}, InvalidLinkException::class, "Training linked in [training:foo] doesn't exist");
	}


	public function testResolveMissingLocale(): void
	{
		$this->database->setFetchPairsDefaultResult(['cs_BŽ' => 'něco']);
		Assert::exception(function (): void {
			$this->resolve('training:fred');
		}, InvalidLinkException::class, "Training linked in [training:fred] doesn't exist in locale cs_CZ");
	}


	private function resolve(string $url): ?string
	{
		$texy = $this->texyFormatter->getTexy();
		$texy->process('');
		$html = $this->shortcutTraining->resolve(
			$url,
			new HandlerInvocation([fn() => 'Školení'], new LineParser($texy, new HtmlElement()), []),
			'',
			'',
			new Modifier(''),
			new Link(''),
		);
		return $html?->toHtml($texy);
	}

}

TestCaseRunner::run(TexyShortcutTrainingTest::class);
