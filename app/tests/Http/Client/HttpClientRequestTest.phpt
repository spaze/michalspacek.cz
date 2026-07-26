<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Http\Client;

use MichalSpacekCz\Http\Exceptions\HttpClientRequestUnsupportedSchemeException;
use MichalSpacekCz\Test\TestCaseRunner;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../../bootstrap.php';

/** @testCase */
final class HttpClientRequestTest extends TestCase
{

	public function testOnlyHttpSchemesAllowed(): void
	{
		$expected = [
			'https://example.com/' => true,
			'http://example.com/' => true,
			'HTTPS://example.net/' => true, // WHATWG lower-cases the scheme
			'HTTP://example.net/' => true, // same here
			'file:///etc/passwd' => false,
			'php://filter/convert.base64-encode/resource=/etc/passwd' => false,
			'ftp://example.com/' => false,
			'gopher://example.com/' => false,
			'data:text/plain,hello' => false,
			'ws://example.com/' => false,
			'/etc/passwd' => false, // no scheme, fopen() would read it as a local path
			'' => false,
		];
		$allowed = [];
		foreach (array_keys($expected) as $url) {
			try {
				new HttpClientRequest($url);
				$allowed[$url] = true;
			} catch (HttpClientRequestUnsupportedSchemeException) {
				$allowed[$url] = false;
			}
		}
		Assert::same($expected, $allowed);
	}

}

TestCaseRunner::run(HttpClientRequestTest::class);
