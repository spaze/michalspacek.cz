<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\Formatter\TexyPhraseHandler;

use MichalSpacekCz\Test\Application\ApplicationPresenter;
use MichalSpacekCz\Test\Application\LocaleLinkGeneratorMock;
use MichalSpacekCz\Test\Database\Database;
use MichalSpacekCz\Test\NoOpTranslator;
use MichalSpacekCz\Test\PrivateProperty;
use MichalSpacekCz\Test\Talks\TalkTestDataFactory;
use MichalSpacekCz\Test\TestCaseRunner;
use MichalSpacekCz\Utils\Arrays;
use Nette\Application\Application;
use Nette\Application\UI\InvalidLinkException;
use Override;
use Tester\Assert;
use Tester\TestCase;
use Texy\Texy;

require __DIR__ . '/../../bootstrap.php';

/** @testCase */
final class TexyPhraseHandlerTest extends TestCase
{

	private const string EN_LOCALE = 'en_US';

	private Texy $texy;


	public function __construct(
		private readonly Database $database,
		private readonly LocaleLinkGeneratorMock $localeLinkGenerator,
		private readonly TalkTestDataFactory $talkDataFactory,
		private readonly NoOpTranslator $translator,
		Application $application,
		ApplicationPresenter $applicationPresenter,
		TexyPhraseHandler $phraseHandler,
	) {
		$this->texy = new Texy();
		$this->texy->addHandler('phrase', $phraseHandler->solve(...));
		$applicationPresenter->setLinkCallback($application, $this->buildUrl(...));
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
			$this->translator->getDefaultLocale() => $defaultLocaleUrl,
			self::EN_LOCALE => $enLocaleUrl,
		]);
		$this->assertUrl('title', $defaultLocaleUrl, '"title":[link:Module:Presenter:action params,foo]');
		Assert::same(['params', 'foo'], $this->localeLinkGenerator->getAllLinksParams()[$this->translator->getDefaultLocale()]);
		$this->assertUrl('title', $enLocaleUrl, '"title":[link-' . self::EN_LOCALE . ':Module:Presenter:action params , bar]');
		Assert::same(['params', 'bar'], $this->localeLinkGenerator->getAllLinksParams()[self::EN_LOCALE]);
	}


	public function testSolveTrainingLink(): void
	{
		$this->database->setFetchPairsDefaultResult([$this->translator->getDefaultLocale() => 'fjó']);
		$defaultLocaleUrl = 'https://cz.example/skoleni/foo';
		$this->localeLinkGenerator->setAllLinks([
			$this->translator->getDefaultLocale() => $defaultLocaleUrl,
		]);
		$this->assertUrl('title', $defaultLocaleUrl, '"title":[link:Www:Trainings:training foo]');
	}


	public function testSolveTalkLink(): void
	{
		// Talk data
		$this->database->setFetchDefaultResult($this->talkDataFactory->getDatabaseResultData());
		// Slide exists
		$this->database->setFetchFieldDefaultResult(1);
		$this->assertUrl(
			'pizza hawaii fan club',
			$this->buildUrl('//:Www:Talks:talk', ['foo', 'bar']),
			'"pizza hawaii fan club":[talk:foo#bar]',
		);
		$this->assertUrl(
			'anti pizza hawaii fan club',
			$this->buildUrl('//:Www:Talks:talk', ['foo']),
			'"anti pizza hawaii fan club":[talk:foo]',
		);
	}


	public function testSolveBlogPostLink(): void
	{
		$this->database->setFetchAllDefaultResult([
			[
				'locale' => $this->translator->getDefaultLocale(),
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
			$this->translator->getDefaultLocale() => $postUrl,
			self::EN_LOCALE => $postEnUrl,
		]);
		$this->assertUrl('le post', $postUrl, '"le post":[blog:post#fragment]');
		$this->assertUrl('le post', $postUrl, '"le post":[blog-' . $this->translator->getDefaultLocale() . ':post#fragment]');
		$this->assertUrl('teh post', $postEnUrl, '"teh post":[blog-' . self::EN_LOCALE . ':post#fragment]');
	}


	public function testSolveBlogPostLinkMissingPost(): void
	{
		Assert::exception(function (): void {
			$this->assertUrl('le post', '[irrelevant]', '"le post":[blog:post#fragment]');
		}, InvalidLinkException::class, "Blog post linked in [blog:post#fragment] doesn't exist");
	}


	public function testSolveInhouseTrainingLink(): void
	{
		$this->database->setFetchPairsDefaultResult([$this->translator->getDefaultLocale() => 'fjó']);
		$this->assertUrl(
			'title',
			$this->buildUrl('//:Www:CompanyTrainings:training', ['fjó']),
			'"title":[inhouse-training:training]',
		);
	}


	public function testSolveTrainingWithDatesLink(): void
	{
		$this->database->setFetchPairsDefaultResult([$this->translator->getDefaultLocale() => 'fjó']);
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
			'args' => implode(',', Arrays::filterEmpty($args)),
		]);
	}


	private function assertUrl(string $title, string $url, string $texyText, string $aHrefSuffixHtml = ''): void
	{
		$expected = '<p><a href="' . htmlspecialchars($url) . "\">{$title}</a>{$aHrefSuffixHtml}</p>";
		Assert::same($expected, str_replace("\n", ' ', trim($this->texy->process($texyText))));
	}

}

TestCaseRunner::run(TexyPhraseHandlerTest::class);
