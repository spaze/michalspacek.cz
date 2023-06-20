<?php
/** @noinspection PhpFullyQualifiedNameUsageInspection */
/** @noinspection PhpDocRedundantThrowsInspection */
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\Formatter;

use MichalSpacekCz\Test\Application\ApplicationPresenter;
use MichalSpacekCz\Test\Application\LocaleLinkGenerator;
use MichalSpacekCz\Test\Database\Database;
use MichalSpacekCz\Test\NoOpTranslator;
use Nette\Application\Application;
use Tester\Assert;
use Tester\TestCase;
use Texy\Texy;

$runner = require __DIR__ . '/../bootstrap.php';

/** @testCase */
class TexyPhraseHandlerTest extends TestCase
{

	private const EN_LOCALE = 'en_US';

	private Texy $texy;
	private string $defaultLocale;


	public function __construct(
		private readonly Database $database,
		private readonly Application $application,
		private readonly ApplicationPresenter $applicationPresenter,
		private readonly LocaleLinkGenerator $localeLinkGenerator,
		private readonly NoOpTranslator $translator,
		private readonly TexyPhraseHandler $phraseHandler,
	) {
	}


	protected function setUp(): void
	{
		$this->texy = new Texy();
		$this->texy->addHandler('phrase', [$this->phraseHandler, 'solve']);
		$this->applicationPresenter->setLinkCallback($this->application, $this->buildUrl(...));
		$this->defaultLocale = $this->translator->getDefaultLocale();
	}


	protected function tearDown(): void
	{
		$this->database->reset();
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


	/**
	 * @throws \MichalSpacekCz\ShouldNotHappenException The blog links array should not be empty, maybe the linked blog post 'post#fragment' is missing?
	 */
	public function testSolveBlogPostLinkMissingPost(): void
	{
		$this->assertUrl('le post', '[irrelevant]', '"le post":[blog:post#fragment]');
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
	 * @param array<int, string> $args
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

$runner->run(TexyPhraseHandlerTest::class);
