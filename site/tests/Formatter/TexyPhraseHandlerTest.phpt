<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\Formatter;

use MichalSpacekCz\ShouldNotHappenException;
use MichalSpacekCz\Test\Application\ApplicationPresenter;
use MichalSpacekCz\Test\Application\LocaleLinkGeneratorMock;
use MichalSpacekCz\Test\Database\Database;
use MichalSpacekCz\Test\NoOpTranslator;
use MichalSpacekCz\Test\PrivateProperty;
use MichalSpacekCz\Test\TestCaseRunner;
use Nette\Application\Application;
use Override;
use Tester\Assert;
use Tester\TestCase;
use Texy\Texy;

require __DIR__ . '/../bootstrap.php';

/** @testCase */
class TexyPhraseHandlerTest extends TestCase
{

	private const string EN_LOCALE = 'en_US';

	private Texy $texy;
	private string $defaultLocale;


	public function __construct(
		private readonly Database $database,
		private readonly LocaleLinkGeneratorMock $localeLinkGenerator,
		Application $application,
		ApplicationPresenter $applicationPresenter,
		NoOpTranslator $translator,
		TexyPhraseHandler $phraseHandler,
	) {
		$this->texy = new Texy();
		$this->texy->addHandler('phrase', $phraseHandler->solve(...));
		$applicationPresenter->setLinkCallback($application, $this->buildUrl(...));
		$this->defaultLocale = $translator->getDefaultLocale();
	}


	#[Override]
	protected function tearDown(): void
	{
		$this->database->reset();
		PrivateProperty::setValue($this->texy, 'processing', false);
	}


	public function testSolveNoLink(): void
	{
		Assert::same('<p><strong>foo</strong></p>', trim($this->texy->process('**foo**')));
	}


	public function testSolveGeneralLink(): void
	{
		$defaultLocaleUrl = 'https://cz.example/prezentr/akce?paramy';
		$enLocaleUrl = 'https://example.com/presenter/action?params';
		$this->localeLinkGenerator->setAllLinks([
			$this->defaultLocale => $defaultLocaleUrl,
			self::EN_LOCALE => $enLocaleUrl,
		]);
		$this->assertUrl('title', $defaultLocaleUrl, '"title":[link:Module:Presenter:action params]');
		$this->assertUrl('title', $enLocaleUrl, '"title":[link-' . self::EN_LOCALE . ':Module:Presenter:action params]');
	}


	public function testSolveTrainingLink(): void
	{
		$this->database->setFetchPairsResult([$this->defaultLocale => 'fjó']);
		$defaultLocaleUrl = 'https://cz.example/skoleni/foo';
		$this->localeLinkGenerator->setAllLinks([
			$this->defaultLocale => $defaultLocaleUrl,
		]);
		$this->assertUrl('title', $defaultLocaleUrl, '"title":[link:Www:Trainings:training foo]');
	}


	public function testSolveBlogPostLink(): void
	{
		$this->database->setFetchAllDefaultResult([
			[
				'locale' => $this->defaultLocale,
				'slug' => 'fó',
				'published' => null,
				'previewKey' => (string)rand(),
				'slugTags' => null,
			],
			[
				'locale' => self::EN_LOCALE,
				'slug' => 'foo',
				'published' => null,
				'previewKey' => (string)rand(),
				'slugTags' => null,
			],
		]);
		$postUrl = 'https://blog.example/fó#fragment';
		$postEnUrl = 'https://blog.example/foo#fragment';
		$this->localeLinkGenerator->setAllLinks([
			$this->defaultLocale => $postUrl,
			self::EN_LOCALE => $postEnUrl,
		]);
		$this->assertUrl('le post', $postUrl, '"le post":[blog:post#fragment]');
		$this->assertUrl('le post', $postUrl, '"le post":[blog-' . $this->defaultLocale . ':post#fragment]');
		$this->assertUrl('teh post', $postEnUrl, '"teh post":[blog-' . self::EN_LOCALE . ':post#fragment]');
	}


	public function testSolveBlogPostLinkMissingPost(): void
	{
		Assert::exception(function (): void {
			$this->assertUrl('le post', '[irrelevant]', '"le post":[blog:post#fragment]');
		}, ShouldNotHappenException::class, "The blog links array should not be empty, maybe the linked blog post 'post#fragment' is missing?");
	}


	public function testSolveInhouseTrainingLink(): void
	{
		$this->database->setFetchPairsResult([$this->defaultLocale => 'fjó']);
		$this->assertUrl(
			'title',
			$this->buildUrl('//:Www:CompanyTrainings:training', ['fjó']),
			'"title":[inhouse-training:training]',
		);
	}


	public function testSolveTrainingWithDatesLink(): void
	{
		$this->database->setFetchPairsResult([$this->defaultLocale => 'fjó']);
		$this->assertUrl(
			'title',
			$this->buildUrl('//:Www:Trainings:training', ['fjó']),
			'"title":[training:training]',
			' <small>(**TRAINING_DATE:fjó**)</small>',
		);
	}


	/**
	 * @param list<mixed> $args
	 */
	private function buildUrl(string $destination, array $args = []): string
	{
		return 'https://example.com/?' . http_build_query([
			'dest' => $destination,
			'args' => implode(',', $args),
		]);
	}


	private function assertUrl(string $title, string $url, string $texyText, string $aHrefSuffixHtml = ''): void
	{
		$expected = '<p><a href="' . htmlspecialchars($url) . "\">{$title}</a>{$aHrefSuffixHtml}</p>";
		Assert::same($expected, str_replace("\n", ' ', trim($this->texy->process($texyText))));
	}

}

TestCaseRunner::run(TexyPhraseHandlerTest::class);
