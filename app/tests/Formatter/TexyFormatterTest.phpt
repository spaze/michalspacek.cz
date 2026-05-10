<?php
/** @noinspection PhpFullyQualifiedNameUsageInspection */
/** @noinspection PhpDocRedundantThrowsInspection */
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\Formatter;

use DateTime;
use MichalSpacekCz\Test\Application\ApplicationPresenter;
use MichalSpacekCz\Test\Database\Database;
use MichalSpacekCz\Test\Http\Request;
use MichalSpacekCz\Test\TestCaseRunner;
use Nette\Application\Application;
use Nette\Utils\Html;
use Override;
use Stringable;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../bootstrap.php';

/** @testCase */
final class TexyFormatterTest extends TestCase
{

	private const string TRAINING_ACTION = 'bezpecnost-php-aplikaci';

	private string $expectedFormatted;

	private string $format;


	public function __construct(
		private readonly TexyFormatter $texyFormatter,
		private readonly AdapterInterface $cacheInterface,
		Request $httpRequest,
		Database $database,
		Application $application,
		ApplicationPresenter $applicationPresenter,
	) {
		$applicationPresenter->setLinkCallback($application, $this->buildUrl(...));
		$database->setFetchAllDefaultResult([
			[
				'dateId' => 1,
				'trainingId' => 1,
				'action' => self::TRAINING_ACTION,
				'name' => 'Bezpečnost PHP aplikací',
				'price' => 3490,
				'studentDiscount' => null,
				'hasCustomPrice' => 0,
				'hasCustomStudentDiscount' => 0,
				'start' => new DateTime('2020-01-05 04:03:02'),
				'end' => new DateTime('2020-01-07 04:03:02'),
				'labelJson' => '{"cs_CZ": "lej-bl", "en_US": "la-bel"}',
				'status' => 'CONFIRMED',
				'public' => 0,
				'remote' => 1,
				'remoteUrl' => null,
				'remoteNotes' => null,
				'venueId' => 1,
				'venueAction' => 'venue-1',
				'venueHref' => 'https://venue1.example/',
				'venueName' => 'Le venue 1',
				'venueNameExtended' => null,
				'venueAddress' => 'Street 22',
				'venueCity' => 'Le city 1',
				'venueDescription' => 'Venue description //1//',
				'cooperationId' => null,
				'cooperationDescription' => null,
				'videoHref' => null,
				'feedbackHref' => null,
				'note' => 'Note 1',
			],
			[
				'dateId' => 2,
				'trainingId' => 1,
				'action' => self::TRAINING_ACTION,
				'name' => 'Bezpečnost PHP aplikací',
				'price' => 4490,
				'studentDiscount' => null,
				'hasCustomPrice' => 0,
				'hasCustomStudentDiscount' => 0,
				'start' => new DateTime('2020-02-05 04:03:02'),
				'end' => new DateTime('2020-02-07 04:03:02'),
				'labelJson' => '{"cs_CZ": "lej-bl", "en_US": "la-bel"}',
				'status' => 'CONFIRMED',
				'public' => 1,
				'remote' => 0,
				'remoteUrl' => null,
				'remoteNotes' => null,
				'venueId' => 2,
				'venueAction' => 'venue-2',
				'venueHref' => 'https://venue2.example/',
				'venueName' => 'Le venue 2',
				'venueNameExtended' => null,
				'venueAddress' => 'Street 22',
				'venueCity' => 'Le city 2',
				'venueDescription' => 'Venue description //2//',
				'cooperationId' => null,
				'cooperationDescription' => null,
				'videoHref' => null,
				'feedbackHref' => null,
				'note' => 'Note 2',
			],
		]);
		$database->setFetchPairsDefaultResult([
			'cs_CZ' => 'bezpecnost-php-aplikaci',
			'en_US' => 'php-application-security',
		]);
		$httpRequest->setHeader('Sec-Fetch-Dest', 'iframe');
		$this->format = '**foo "bar":[training:' . self::TRAINING_ACTION . "]**\n"
			. "''**FETCH_METADATA:Sec-Fetch-Dest**''\n"
			. "''**FETCH_METADATA:all**''\n";
		$this->expectedFormatted = "<strong>foo <a\n"
			. "href=\"https://example.com/?dest=%2F%2F%3AWww%3ATrainings%3Atraining&amp;args=bezpecnost-php-aplikaci\">bar</a>\n"
			. "<small>(messages.trainings.nextdates: <strong>5.–7. ledna 2020</strong> messages.label.remote, <strong>5.–7. února 2020</strong> Le city 2)</small></strong>\n"
			. "Sec-Fetch-Dest: iframe Sec-Fetch-Dest: iframe\n"
			. "Sec-Fetch-Mode: <em>[messages.httpHeaders.headerNotSent]</em>\n"
			. "Sec-Fetch-Site: <em>[messages.httpHeaders.headerNotSent]</em>\n"
			. "Sec-Fetch-User: <em>[messages.httpHeaders.headerNotSent]</em>";
	}


	public function testFormat(): void
	{
		Assert::same($this->expectedFormatted, $this->texyFormatter->format($this->format)->toHtml());
	}


	public function testFormatBlock(): void
	{
		Assert::same("<p>{$this->expectedFormatted}</p>\n", $this->texyFormatter->formatBlock($this->format)->toHtml());
	}


	public function testDisableCache(): void
	{
		$text = '**anoff**';
		$expected = '<strong>anoff</strong>';

		$key = $this->texyFormatter->getCacheKey("{$text}|format", $this->texyFormatter->getTexy());
		Assert::same($expected, $this->texyFormatter->format($text)->toHtml());
		Assert::true($this->cacheInterface->getItem($key)->isHit());

		$this->cacheInterface->clear();
		$this->texyFormatter->disableCache();
		Assert::same($expected, $this->texyFormatter->format($text)->toHtml());
		Assert::false($this->cacheInterface->getItem($key)->isHit());
	}


	public function testSubstitute(): void
	{
		Assert::same('<em>foo</em> bar 303', $this->texyFormatter->substitute('*foo* %s %d', ['bar', 303])->render());

		$toString = new class () implements Stringable {

			#[Override]
			public function __toString(): string
			{
				return __FUNCTION__;
			}

		};
		$toStringNoInterface = new class () {

			#[Override]
			public function __toString(): string
			{
				return __FUNCTION__;
			}

		};
		$html = Html::fromText('foo');
		Assert::same('__toString', $this->texyFormatter->substitute($toString, [])->render());
		Assert::same('<code>__toString</code>', $this->texyFormatter->substitute("`%s`", [$toString])->render());
		Assert::same('__toString', $this->texyFormatter->substitute($toStringNoInterface, [])->render());
		Assert::same('<code>__toString</code>', $this->texyFormatter->substitute("`%s`", [$toStringNoInterface])->render());

		Assert::same('foo', $this->texyFormatter->substitute($html, [])->render());
		Assert::same('<strong>foo</strong>', $this->texyFormatter->substitute("**%s**", [$html])->render());
	}


	public function testSetTopHeadingUpdatesNoLongWordsTexy(): void
	{
		$this->texyFormatter->substituteText('%s', ['init']); // Initialize $texyNoLongWords
		$this->texyFormatter->setTopHeading(2);
		Assert::same("<h2 id=\"title\">Title</h2>\n\n<p>`foo`</p>\n", $this->texyFormatter->substituteText("Title\n#####\n%s", ['`foo`'])->render());
	}


	public function testSubstituteText(): void
	{
		Assert::same('foo', $this->texyFormatter->substituteText('%s', ['foo'])->render());
		Assert::same('42', $this->texyFormatter->substituteText('%s', [42])->render());
		Assert::same('&lt;b&gt;bold&lt;/b&gt;', $this->texyFormatter->substituteText('%s', ['<b>bold</b>'])->render());
		Assert::same('a &amp; b', $this->texyFormatter->substituteText('%s', ['a & b'])->render());
		Assert::same('&quot;quoted&quot;', $this->texyFormatter->substituteText('%s', ['"quoted"'])->render());
		Assert::same('&#039;single-quoted&#039;', $this->texyFormatter->substituteText('%s', ["'single-quoted'"])->render());
		Assert::same('**bold**', $this->texyFormatter->substituteText('%s', ['**bold**'])->render());
		Assert::same('<em>foo</em>', $this->texyFormatter->substituteText('//%s//', ['foo'])->render());
		Assert::same('<em>&lt;script&gt;alert(1)&lt;/script&gt;</em>', $this->texyFormatter->substituteText('//%s//', ['<script>alert(1)</script>'])->render());
		Assert::same('foo,bar', $this->texyFormatter->substituteText('%s,%s', ['foo', 'bar'])->render());
		Assert::same('foo `bar` baz', $this->texyFormatter->substituteText('%s', ['foo `bar` baz'])->render());
	}


	/**
	 * @param list<string> $args
	 */
	private function buildUrl(string $destination, array $args): string
	{
		return 'https://example.com/?' . http_build_query([
				'dest' => $destination,
				'args' => implode(',', $args),
			]);
	}

}

TestCaseRunner::run(TexyFormatterTest::class);
