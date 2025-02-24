<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Http;

use MichalSpacekCz\Http\Exceptions\HttpRedirectDestinationUrlMalformedException;
use MichalSpacekCz\Test\Database\Database;
use MichalSpacekCz\Test\TestCaseRunner;
use Nette\Http\UrlScript;
use Tester\Assert;
use Tester\TestCase;

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

		$this->database->setFetchFieldDefaultResult('/foo.bar');
		Assert::same('https://com.example/foo.bar', $this->redirections->getDestination(new UrlScript('https://com.example/waldo')));

		Assert::exception(function (): void {
			$this->database->setFetchFieldDefaultResult('https://:80');
			$this->redirections->getDestination(new UrlScript());
		}, HttpRedirectDestinationUrlMalformedException::class, "Redirect destination 'https://:80' is a seriously malformed URL");
	}

}

TestCaseRunner::run(RedirectionsTest::class);
