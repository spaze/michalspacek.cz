<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\Utils;

use MichalSpacekCz\Test\TestCaseRunner;
use Tester\Assert;
use Tester\TestCase;
use Uri\WhatWg\Url;

require __DIR__ . '/../bootstrap.php';

/** @testCase */
final class UrlOriginTest extends TestCase
{

	public function __construct(
		private readonly UrlOrigin $urlOrigin,
	) {
	}


	/**
	 * @return list<array{0:string, 1:string|null}>
	 */
	public function getUrl(): array
	{
		return [
			['https://example.com', 'https://example.com'],
			['https://example.com/', 'https://example.com'],
			['https://example.com/foo', 'https://example.com'],
			['https://example.com:0', 'https://example.com:0'],
			['https://example.com:123', 'https://example.com:123'],
			['https://example.com:0/', 'https://example.com:0'],
			['https://example.com:123/', 'https://example.com:123'],
			['https://example.com:0/foo', 'https://example.com:0'],
			['https://example.com:123/foo', 'https://example.com:123'],
			['omg://wut/bbq', null],
			['file:///pizza', null],
			['ftp://wut/bbq', 'ftp://wut'],
			['http://wut/bbq', 'http://wut'],
			['http:///wut/bbq', 'http://wut'],
			['https:////wut/bbq', 'https://wut'],
			['https:wut/bbq', 'https://wut'],
			['ws://wut/bbq', 'ws://wut'],
			['wss://wut/bbq', 'wss://wut'],
		];
	}


	/**
	 * @dataProvider getUrl
	 */
	public function testGetFromUrl(string $url, ?string $origin): void
	{
		if ($origin === null) {
			Assert::null($this->urlOrigin->getFromUrl(new Url($url)));
		} else {
			Assert::same($origin, $this->urlOrigin->getFromUrl(new Url($url)));
		}
	}

}

TestCaseRunner::run(UrlOriginTest::class);
