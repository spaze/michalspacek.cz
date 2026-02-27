<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\Formatter\TexyPhraseHandler\Shortcuts;

use MichalSpacekCz\Formatter\TexyFormatter;
use MichalSpacekCz\Test\Application\ApplicationPresenter;
use MichalSpacekCz\Test\Application\LocaleLinkGeneratorMock;
use MichalSpacekCz\Test\Database\Database;
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
final class TexyShortcutInhouseTrainingTest extends TestCase
{

	public function __construct(
		private readonly TexyShortcutInhouseTraining $shortcutInhouseTraining,
		private readonly TexyFormatter $texyFormatter,
		private readonly Database $database,
		LocaleLinkGeneratorMock $localeLinkGenerator,
		ApplicationPresenter $applicationPresenter,
		Application $application,
	) {
		$applicationPresenter->setLinkCallback($application, fn(string $destination, array $args) => $destination . ' ' . implode(',', $args));
		$localeLinkGenerator->setAllLinks(['cs_CZ' => 'https://com.example/']);
	}


	#[Override]
	protected function tearDown(): void
	{
		$this->database->reset();
	}


	public function testCanResolve(): void
	{
		Assert::true($this->shortcutInhouseTraining->canResolve('inhouse-training:'));
		Assert::false($this->shortcutInhouseTraining->canResolve('Inhouse-training:'));
		Assert::false($this->shortcutInhouseTraining->canResolve('inhousetraining:'));
		Assert::false($this->shortcutInhouseTraining->canResolve('foo:'));
	}


	public function testResolve(): void
	{
		$this->database->setFetchPairsDefaultResult(['cs_CZ' => 'něco']);
		$link = new Link('');
		$this->resolve('inhouse-training:foo', $link);
		Assert::same('//:Www:CompanyTrainings:training něco', $link->URL);
	}


	public function testResolveNoTraining(): void
	{
		$this->database->reset();
		Assert::exception(function (): void {
			$this->resolve('inhouse-training:', new Link(''));
		}, InvalidLinkException::class, 'No company training specified in [inhouse-training:]');
	}


	public function testResolveTrainingDoesNotExistInLocale(): void
	{
		$this->database->setFetchPairsDefaultResult(['pt_BR' => 'bar']);
		Assert::exception(function (): void {
			$this->resolve('inhouse-training:foo', new Link(''));
		}, InvalidLinkException::class, "Company training linked in [inhouse-training:foo] doesn't exist in locale cs_CZ");
	}


	private function resolve(string $url, Link $link): void
	{
		$texy = $this->texyFormatter->getTexy();
		$texy->process('');
		$result = $this->shortcutInhouseTraining->resolve(
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

TestCaseRunner::run(TexyShortcutInhouseTrainingTest::class);
