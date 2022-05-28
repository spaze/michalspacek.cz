<?php
/** @noinspection PhpFullyQualifiedNameUsageInspection */
/** @noinspection PhpDocRedundantThrowsInspection */
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\Formatter;

use Closure;
use MichalSpacekCz\Post\LocaleUrls;
use MichalSpacekCz\Test\Application\LocaleLinkGenerator;
use MichalSpacekCz\Test\Database\Database;
use MichalSpacekCz\Test\NoOpTranslator;
use MichalSpacekCz\Test\ServicesTrait;
use MichalSpacekCz\Training\Locales;
use Nette\Application\Application;
use Nette\Application\UI\Presenter;
use ReflectionProperty;
use Tester\Assert;
use Tester\TestCase;
use Texy\Texy;

require __DIR__ . '/../bootstrap.php';

/** @testCase */
class TexyPhraseHandlerTest extends TestCase
{

	use ServicesTrait;


	private Database $database;
	private Application $application;
	private Locales $trainingLocales;
	private LocaleLinkGenerator $localeLinkGenerator;
	private LocaleUrls $blogPostLocaleUrls;
	private NoOpTranslator $translator;
	private TexyPhraseHandler $phraseHandler;
	private Texy $texy;
	private string $locale;


	protected function setUp()
	{
		$this->database = $this->getDatabase();
		$this->application = $this->getApplication();
		$this->trainingLocales = $this->getLocales();
		$this->localeLinkGenerator = $this->getLocaleLinkGenerator();
		$this->blogPostLocaleUrls = $this->getBlogPostLocaleUrls();
		$this->translator = $this->getTranslator();
		$this->phraseHandler = new TexyPhraseHandler($this->application, $this->trainingLocales, $this->localeLinkGenerator, $this->blogPostLocaleUrls, $this->translator);

		$this->texy = new Texy();
		$this->texy->addHandler('phrase', [$this->phraseHandler, 'solve']);
		$property = new ReflectionProperty($this->application, 'presenter');
		$property->setValue($this->application, new class ($this->buildUrl(...)) extends Presenter {

			/**
			 * @param Closure(string, string[]): string $buildLink
			 * @noinspection PhpMissingParentConstructorInspection
			 */
			public function __construct(
				private readonly Closure $buildLink,
			) {
			}


			public function link(string $destination, $args = []): string
			{
				$args = func_num_args() < 3 && is_array($args)
					? $args
					: array_slice(func_get_args(), 1);
				return ($this->buildLink)($destination, $args);
			}

		});
		$this->locale = $this->translator->getDefaultLocale();
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
		$this->assertUrl(
			'title',
			$this->buildUrl('//:Module:Presenter:action', ['params']),
			'"title":[link:Module:Presenter:action params]',
		);
	}


	public function testSolveTrainingLink(): void
	{
		$this->database->setFetchPairsResult([$this->locale => 'fjó']);
		$this->assertUrl(
			'title',
			$this->buildUrl('//:Www:Trainings:training', ['fjó']),
			'"title":[link:Www:Trainings:training foo]',
		);
	}


	public function testSolveBlogPostLink(): void
	{
		$enLocale = 'en_US';
		$this->database->setFetchAllResult([
			[
				'locale' => $this->locale,
				'slug' => 'fó',
				'published' => null,
				'previewKey' => (string)rand(),
				'slugTags' => null,
			],
			[
				'locale' => $enLocale,
				'slug' => 'foo',
				'published' => null,
				'previewKey' => (string)rand(),
				'slugTags' => null,
			],
		]);
		$postUrl = 'https://blog.example/fó#fragment';
		$postEnUrl = 'https://blog.example/foo#fragment';
		$this->localeLinkGenerator->setAllLinks([
			$this->locale => $postUrl,
			$enLocale => $postEnUrl,
		]);
		$this->assertUrl('le post', $postUrl, '"le post":[blog:post#fragment]');
		$this->assertUrl('le post', $postUrl, '"le post":[blog-' . $this->locale . ':post#fragment]');
		$this->assertUrl('teh post', $postEnUrl, '"teh post":[blog-' . $enLocale . ':post#fragment]');
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
		$this->database->setFetchPairsResult([$this->locale => 'fjó']);
		$this->assertUrl(
			'title',
			$this->buildUrl('//:Www:CompanyTrainings:training', ['fjó']),
			'"title":[inhouse-training:training]',
		);
	}


	public function testSolveTrainingWithDatesLink(): void
	{
		$this->database->setFetchPairsResult([$this->locale => 'fjó']);
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

(new TexyPhraseHandlerTest())->run();
