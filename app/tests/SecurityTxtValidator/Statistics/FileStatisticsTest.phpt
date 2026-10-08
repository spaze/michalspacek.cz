<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\SecurityTxtValidator\Statistics;

use DateTimeImmutable;
use MichalSpacekCz\Test\TestCaseRunner;
use Spaze\SecurityTxt\Check\SecurityTxtCheckHostResult;
use Spaze\SecurityTxt\Check\SecurityTxtCheckHostResultFactory;
use Spaze\SecurityTxt\Fetcher\Exceptions\SecurityTxtHostNotFoundException;
use Spaze\SecurityTxt\Fetcher\Exceptions\SecurityTxtNotFoundException;
use Spaze\SecurityTxt\Fetcher\SecurityTxtFetchResult;
use Spaze\SecurityTxt\Fetcher\SecurityTxtIpAddressType;
use Spaze\SecurityTxt\Parser\SecurityTxtParser;
use Spaze\SecurityTxt\Parser\SecurityTxtSplitLines;
use Spaze\SecurityTxt\SecurityTxtHost;
use Tester\Assert;
use Tester\TestCase;
use Uri\WhatWg\Url;

require __DIR__ . '/../../bootstrap.php';

/** @testCase */
final class FileStatisticsTest extends TestCase
{

	public function __construct(
		private readonly FileStatistics $fileStatistics,
		private readonly SecurityTxtParser $parser,
		private readonly SecurityTxtCheckHostResultFactory $checkHostResultFactory,
		private readonly SecurityTxtSplitLines $splitLines,
	) {
	}


	/**
	 * Real files through the real parser, so what is pinned is what a response of each kind is counted under, not what
	 * the library was assumed to say about it. The metrics and buckets are spelled out as the strings that end up in
	 * the table: a class renamed in the library changes a bucket, and that is worth knowing about.
	 *
	 * @return array<string, array{0: string, 1: list<array{string, string}>}>
	 */
	private function getFiles(): array
	{
		$contact = "Contact: mailto:security@example.com\n";
		return [
			'nothing wrong' => [
				$contact . $this->expires('+100 days'),
				[
					['verdict', 'valid'],
					['expires', 'within_year'],
				],
			],
			'a warning' => [
				$contact . $this->expires('+400 days'),
				[
					['verdict', 'valid_with_warnings'],
					['issue', 'SecurityTxtExpiresTooLong'],
					['expires', 'over_year'],
				],
			],
			'an error' => [
				$contact,
				[
					['verdict', 'invalid'],
					['issue', 'SecurityTxtNoExpires'],
				],
			],
			'expired' => [
				$contact . $this->expires('-1 day'),
				[
					['verdict', 'invalid'],
					['issue', 'SecurityTxtExpired'],
					['expires', 'expired'],
				],
			],
			'about to expire' => [
				$contact . $this->expires('+3 days'),
				[
					['verdict', 'valid'],
					['expires', 'within_month'],
				],
			],
			'the same problem on two lines' => [
				"Contact: http://example.com/security\nContact: http://example.com/report\n" . $this->expires('+100 days'),
				[
					['verdict', 'invalid'],
					['issue', 'SecurityTxtContactNotHttps'],
					['expires', 'within_year'],
				],
			],
		];
	}


	public function testAFileIsCountedUnderWhatItSays(): void
	{
		$expected = $actual = [];
		foreach ($this->getFiles() as $name => [$contents, $counters]) {
			$expected[$name] = $counters;
			$actual[$name] = $this->counters($this->fileStatistics->forCheckHostResult($this->checkHostResult($contents)));
		}
		Assert::same($expected, $actual);
	}


	/**
	 * The same file pasted counts the same as fetched: it is often a file out there that the fetcher was refused.
	 */
	public function testAPastedFileIsCountedLikeAFetchedOne(): void
	{
		$expected = $actual = [];
		foreach ($this->getFiles() as $name => [$contents, $counters]) {
			$expected[$name] = $counters;
			$actual[$name] = $this->counters($this->fileStatistics->forParseStringResult($this->parser->parseString($contents)));
		}
		Assert::same($expected, $actual);
	}


	/**
	 * A host that answered and has no file is a different story from one that could not be asked, and nothing else
	 * about a failure is worth a counter: there is no file to have issues or an expiry.
	 */
	public function testAFailureIsCountedByWhetherTheHostAnswered(): void
	{
		$url = new Url('https://example.com/.well-known/security.txt');
		$notFound = new SecurityTxtNotFoundException(
			[$url->toAsciiString() => ['ip' => '1.2.3.4', 'type' => SecurityTxtIpAddressType::V4->value, 'code' => 404, 'redirects' => [], 'html' => false, 'truncated' => false]],
			$url,
		);
		Assert::same(
			[['verdict', 'not_found']],
			$this->counters($this->fileStatistics->forFetchFailure($notFound)),
		);
		Assert::same(
			[['verdict', 'fetch_failed']],
			$this->counters($this->fileStatistics->forFetchFailure(new SecurityTxtHostNotFoundException($url, new SecurityTxtHost($url)))),
		);
	}


	/**
	 * @param list<StatisticsBucket> $buckets
	 * @return list<array{string, string}>
	 */
	private function counters(array $buckets): array
	{
		return array_map(fn(StatisticsBucket $bucket): array => [$bucket->getMetric()->value, $bucket->getBucket()], $buckets);
	}


	private function checkHostResult(string $contents): SecurityTxtCheckHostResult
	{
		$url = new Url('https://example.com/.well-known/security.txt');
		$fetchResult = new SecurityTxtFetchResult($url, $url, [], $contents, false, $this->splitLines->splitLines($contents), [], []);
		return $this->checkHostResultFactory->create(new SecurityTxtHost($url), $this->parser->parseFetchResult($fetchResult));
	}


	private function expires(string $modifier): string
	{
		return 'Expires: ' . new DateTimeImmutable($modifier)->format(DATE_RFC3339) . "\n";
	}

}

TestCaseRunner::run(FileStatisticsTest::class);
