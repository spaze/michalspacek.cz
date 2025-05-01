<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\SecurityTxtValidator;

use MichalSpacekCz\SecurityTxtValidator\Exceptions\SecurityTxtValidatorHostException;
use MichalSpacekCz\SecurityTxtValidator\SecurityTxtValidatorUrl;
use MichalSpacekCz\Test\TestCaseRunner;
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
		Assert::same($whatWgUrl, $url->getUrl());
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

}

TestCaseRunner::run(SecurityTxtValidatorUrlTest::class);
