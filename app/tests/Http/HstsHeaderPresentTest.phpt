<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\Http;

use MichalSpacekCz\Http\Client\HttpClient;
use MichalSpacekCz\Http\Client\HttpClientRequest;
use MichalSpacekCz\Test\TestCaseRunner;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../bootstrap.php';

/** @testCase */
final class HstsHeaderPresentTest extends TestCase
{

	private const string EXPECTED_HSTS = 'max-age=31536000; includeSubDomains; preload';


	public function testHstsSentOnEveryResponseClass(): void
	{
		TestCaseRunner::needsInternet();
		$urls = [
			'https://www.michalspacek.cz/', // app page, inherits the server-level header
			'https://www.michalspacek.cz/robots.txt', // static file, common-headers-static.conf
			'https://www.michalspacek.cz/security.txt', // 301 redirect, common-headers-redir&notfound.conf
			'https://www.michalspacek.cz/there-is-no.php', // nginx 404, common-headers-redir&notfound.conf
		];
		$client = new HttpClient();
		$actual = [];
		foreach ($urls as $url) {
			$request = new HttpClientRequest($url)
				->setFollowLocation(false) // need a redirect's own headers, not the target's
				->setIgnoreHttpErrors(true); // the 404 is a response to inspect, not an error to throw on
			$actual[$url] = $client->get($request)->getHeader('Strict-Transport-Security');
		}
		Assert::same(array_fill_keys($urls, self::EXPECTED_HSTS), $actual);
	}

}

TestCaseRunner::run(HstsHeaderPresentTest::class);
