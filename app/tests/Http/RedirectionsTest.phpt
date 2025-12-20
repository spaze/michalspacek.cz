<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Http;

use MichalSpacekCz\Http\Exceptions\HttpRedirectDestinationUrlMalformedException;
use MichalSpacekCz\Test\Database\Database;
use MichalSpacekCz\Test\TestCaseRunner;
use Nette\Http\UrlScript;
use Tester\Assert;
use Tester\TestCase;
use Uri\WhatWg\InvalidUrlException;

require __DIR__ . '/../bootstrap.php';

/** @testCase */
final class RedirectionsTest extends TestCase
{

	public function __construct(
		private readonly Redirections $redirections,
		private readonly Database $database,
	) {
	}


	public function testGetDestination(): void
	{
		$this->database->setFetchFieldDefaultResult(null);
		Assert::null($this->redirections->getDestination(new UrlScript()));

		$this->database->setFetchFieldDefaultResult('https://example.com/');
		Assert::same('https://example.com/', $this->redirections->getDestination(new UrlScript()));

		$this->database->setFetchFieldDefaultResult('https://example.net/');
		Assert::same('https://example.net/', $this->redirections->getDestination(new UrlScript('https://com.example/waldo')));

		$this->database->setFetchFieldDefaultResult('https://špaček.example/foo?bar');
		Assert::same('https://špaček.example/foo?bar', $this->redirections->getDestination(new UrlScript('https://com.example/waldo')));

		$this->database->setFetchFieldDefaultResult('foo.bar');
		Assert::same('https://com.example/waldo/foo.bar', $this->redirections->getDestination(new UrlScript('https://com.example/waldo/')));

		$this->database->setFetchFieldDefaultResult('foo.bar');
		Assert::same('https://com.example/foo.bar', $this->redirections->getDestination(new UrlScript('https://com.example/waldo')));

		$this->database->setFetchFieldDefaultResult('/foo.bar');
		Assert::same('https://com.example/foo.bar', $this->redirections->getDestination(new UrlScript('https://com.example/waldo')));

		$this->database->setFetchFieldDefaultResult('?foo=bar');
		Assert::same('https://com.example/waldo?foo=bar', $this->redirections->getDestination(new UrlScript('https://com.example/waldo')));

		$this->database->setFetchFieldDefaultResult('/ček.foo.bar?špa=ček');
		Assert::same('https://com.špaček.example/%C4%8Dek.foo.bar?%C5%A1pa=%C4%8Dek', $this->redirections->getDestination(new UrlScript('https://com.špaček.example/waldo?špaček')));

		$this->database->setFetchFieldDefaultResult(':-/');
		$e = Assert::exception(function (): void {
			$this->redirections->getDestination(new UrlScript());
		}, HttpRedirectDestinationUrlMalformedException::class, "Redirect destination ':-/' is a seriously malformed URL");
		assert($e instanceof HttpRedirectDestinationUrlMalformedException);
		Assert::type(InvalidUrlException::class, $e->getPrevious());
	}

}

TestCaseRunner::run(RedirectionsTest::class);
