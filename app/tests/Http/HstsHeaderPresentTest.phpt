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
final class HstsHeaderPresentTest extends TestCase
{

	private const string HTTPS_EXPECTED_HSTS = 'max-age=31536000; includeSubDomains; preload';
	private const null HTTP_EXPECTED_HSTS = null;


	public function testHstsSentOnEveryResponseClassButNotOnHttp(): void
	{
		TestCaseRunner::needsInternet();
		$httpsUrls = [
			'https://www.michalspacek.cz/', // app page, inherits the server-level header
			'https://www.michalspacek.cz/robots.txt', // static file, common-headers-static.conf
			'https://www.michalspacek.cz/security.txt', // 301 redirect, common-headers-redir&notfound.conf
			'https://www.michalspacek.cz/there-is-no.php', // nginx 404, common-headers-redir&notfound.conf
		];
		$client = new HttpClient();
		$httpsActual = $httpActual = $httpUrls = [];
		foreach ($httpsUrls as $httpsUrl) {
			$request = new HttpClientRequest($httpsUrl)
				->setFollowLocation(false) // need a redirect's own headers, not the target's
				->setIgnoreHttpErrors(true); // the 404 is a response to inspect, not an error to throw on
			$httpUrl = new Url($httpsUrl)->withScheme('http')->toUnicodeString();
			$httpUrls[] = $httpUrl;
			$httpsActual[$httpsUrl] = $client->get($request)->getHeader('Strict-Transport-Security');
			$httpActual[$httpUrl] = $client->get($request->withUrl($httpUrl))->getHeader('Strict-Transport-Security');
		}
		Assert::same(array_fill_keys($httpsUrls, self::HTTPS_EXPECTED_HSTS), $httpsActual);
		Assert::same(array_fill_keys($httpUrls, self::HTTP_EXPECTED_HSTS), $httpActual);
	}

}

TestCaseRunner::run(HstsHeaderPresentTest::class);
