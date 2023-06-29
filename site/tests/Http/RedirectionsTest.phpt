<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Http;

use MichalSpacekCz\ShouldNotHappenException;
use MichalSpacekCz\Test\Database\Database;
use Nette\Http\UrlScript;
use Tester\Assert;
use Tester\TestCase;

$runner = require __DIR__ . '/../bootstrap.php';

/** @testCase */
class RedirectionsTest extends TestCase
{

	public function __construct(
		private readonly Redirections $redirections,
		private readonly Database $database,
	) {
	}


	public function testGetDestination(): void
	{
		$this->database->setFetchFieldResult(null);
		Assert::null($this->redirections->getDestination(new UrlScript()));

		$this->database->setFetchFieldResult(false);
		Assert::null($this->redirections->getDestination(new UrlScript()));

		$this->database->setFetchFieldResult('https://example.com/');
		Assert::same('https://example.com/', $this->redirections->getDestination(new UrlScript()));

		$this->database->setFetchFieldResult('/foo.bar');
		Assert::same('https://com.example/foo.bar', $this->redirections->getDestination(new UrlScript('https://com.example/waldo')));

		Assert::throws(function (): void {
			$this->database->setFetchFieldResult(3.14);
			$this->redirections->getDestination(new UrlScript('https://com.example/waldo'));
		}, ShouldNotHappenException::class, "Redirect destination for '/waldo' is a float not a string");
	}

}

$runner->run(RedirectionsTest::class);
