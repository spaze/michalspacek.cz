<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\Http;

use MichalSpacekCz\Http\Client\HttpClient;
use MichalSpacekCz\Http\Client\HttpClientRequest;
use MichalSpacekCz\Test\TestCaseRunner;
use Tester\Assert;
use Tester\TestCase;
use Uri\WhatWg\Url;

require __DIR__ . '/../bootstrap.php';

/** @testCase */
final class SecurityHeadersPresentTest extends TestCase
{

	private const string HSTS = 'max-age=31536000; includeSubDomains; preload';


	/**
	 * @return array<string, array{url: string, corp: string}>
	 */
	public function getResponseClasses(): array
	{
		// CORP is same-origin for app responses, cross-origin for the public nginx-served ones
		return [
			'app page' => ['url' => 'https://www.michalspacek.cz/', 'corp' => 'same-origin'],
			'static file' => ['url' => 'https://www.michalspacek.cz/robots.txt', 'corp' => 'cross-origin'],
			'301 redirect' => ['url' => 'https://www.michalspacek.cz/security.txt', 'corp' => 'cross-origin'],
			'nginx 404' => ['url' => 'https://www.michalspacek.cz/there-is-no.php', 'corp' => 'cross-origin'],
			'api subdomain' => ['url' => 'https://api.michalspacek.cz/', 'corp' => 'same-origin'],
			'pulse subdomain' => ['url' => 'https://pulse.michalspacek.cz/', 'corp' => 'same-origin'],
		];
	}


	/**
	 * @dataProvider getResponseClasses
	 */
	public function testSecurityHeadersSent(string $url, string $corp): void
	{
		TestCaseRunner::needsInternet();
		$request = new HttpClientRequest($url)
			->setFollowLocation(false) // need a redirect's own headers, not the target's
			->setIgnoreHttpErrors(true); // a 404 is a response to inspect, not an error to throw on
		$response = new HttpClient()->get($request);
		$expected = [
			'Strict-Transport-Security' => self::HSTS,
			'X-Content-Type-Options' => 'nosniff',
			'X-Frame-Options' => 'DENY',
			'Cross-Origin-Opener-Policy' => 'same-origin; report-to="default"',
			'Cross-Origin-Resource-Policy' => $corp,
			'Cross-Origin-Embedder-Policy-Report-Only' => 'require-corp; report-to="default"',
		];
		$actual = [];
		foreach (array_keys($expected) as $name) {
			$actual[$name] = $response->getHeader($name);
		}
		Assert::same($expected, $actual);
	}


	/**
	 * @dataProvider getResponseClasses
	 */
	public function testHstsNeverSentOverHttp(string $url): void
	{
		TestCaseRunner::needsInternet();
		$request = new HttpClientRequest(new Url($url)->withScheme('http')->toUnicodeString())
			->setFollowLocation(false)
			->setIgnoreHttpErrors(true);
		Assert::null(new HttpClient()->get($request)->getHeader('Strict-Transport-Security'));
	}

}

TestCaseRunner::run(SecurityHeadersPresentTest::class);
