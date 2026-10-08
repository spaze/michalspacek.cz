<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\SecurityTxtValidator;

use MichalSpacekCz\SecurityTxtValidator\Exceptions\SecurityTxtValidatorHostException;
use MichalSpacekCz\SecurityTxtValidator\SecurityTxtValidatorUrl;
use MichalSpacekCz\ShouldNotHappenException;
use MichalSpacekCz\Test\TestCaseRunner;
use Spaze\SecurityTxt\Parser\SecurityTxtUrlParser;
use Tester\Assert;
use Tester\TestCase;
use Uri\WhatWg\Url;

require __DIR__ . '/../bootstrap.php';

/** @testCase */
final class SecurityTxtValidatorUrlTest extends TestCase
{

	public function testConstructorNoHostname(): void
	{
		$whatWgUrl = new Url('file:/foo');
		Assert::exception(function () use ($whatWgUrl) {
			new SecurityTxtValidatorUrl($whatWgUrl);
		}, SecurityTxtValidatorHostException::class, 'No hostname');
	}


	public function testGetHostUrl(): void
	{
		$whatWgUrl = new Url('https://foó.example/bar');
		$url = new SecurityTxtValidatorUrl($whatWgUrl);
		Assert::same($whatWgUrl, $url->getBaseUrl());
		Assert::same($whatWgUrl->getUnicodeHost(), $url->getHost());
		Assert::same('foó.example', $url->getHost());
		Assert::same('xn--fo-6ja.example', $url->getAsciiHost());
	}


	/**
	 * The cache is keyed on the ASCII host in a column whose collation ignores accents, so an accented hostname and
	 * its unaccented lookalike must not end up with the same key, or one would be served the other's cached result.
	 */
	public function testAccentedHostDoesNotShareAKeyWithItsLookalike(): void
	{
		$accented = new SecurityTxtValidatorUrl(new Url('https://foó.example/'));
		$plain = new SecurityTxtValidatorUrl(new Url('https://foo.example/'));
		Assert::same('foó.example', $accented->getHost());
		Assert::same('foo.example', $plain->getHost());
		Assert::notSame($plain->getAsciiHost(), $accented->getAsciiHost());
	}


	/**
	 * The scheme, the host and the port together are what decides which file gets checked, so they are what the cache is
	 * keyed on. A default port is normalized away by the URL parser, so it is named here instead, or the same check would
	 * be keyed two ways.
	 */
	public function testGetSchemeAndPort(): void
	{
		$parser = new SecurityTxtUrlParser();
		$key = function (string $url) use ($parser): string {
			$validatorUrl = new SecurityTxtValidatorUrl($parser->getBaseUrl($parser->getUrl($url)));
			return sprintf('%s|%s|%s', $validatorUrl->getScheme(), $validatorUrl->getAsciiHost(), $validatorUrl->getPort());
		};
		Assert::same('https|example.com|443', $key('example.com'));
		Assert::same('https|example.com|443', $key('https://example.com:443/foo'));
		Assert::same('https|example.com|443', $key('http://example.com/foo')); // the parser asks for https, so this is the same check
		Assert::same('https|example.com|8443', $key('//example.com:8443'));
		Assert::same('https|example.com|8443', $key('https://example.com:8443/foo'));
		Assert::same('https|xn--fo-6ja.example|8443', $key('https://foó.example:8443/'));
		Assert::notSame($key('example.com'), $key('//example.com:8443'));
	}


	/**
	 * The guard in `SecurityTxtValidatorHost` means a scheme with no default port never reaches here, so this says what
	 * happens if one ever does: refuse, rather than invent a port and file an answer under a key nothing can be fetched
	 * over.
	 */
	public function testAnUnfetchableSchemeHasNoPortToOffer(): void
	{
		$parser = new SecurityTxtUrlParser();
		$url = new SecurityTxtValidatorUrl($parser->getBaseUrl($parser->getUrl('foo://example.com')));
		Assert::same('foo', $url->getScheme());
		Assert::exception(function () use ($url): void {
			$url->getPort();
		}, ShouldNotHappenException::class, 'No default port known for the foo scheme');
	}

}

TestCaseRunner::run(SecurityTxtValidatorUrlTest::class);
