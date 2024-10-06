<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\Application;

use MichalSpacekCz\Test\TestCaseRunner;
use Nette\Application\UI\InvalidLinkException;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../bootstrap.php';

/** @testCase */
class LinkGeneratorTest extends TestCase
{

	public function __construct(
		private readonly LinkGenerator $linkGenerator,
	) {
	}


	public function testLink(): void
	{
		Assert::noError(function (): void {
			$this->linkGenerator->link('Www:Homepage:');
		});
		Assert::exception(function (): void {
			$this->linkGenerator->link('');
		}, InvalidLinkException::class, "Invalid destination ''.");
	}

}

TestCaseRunner::run(LinkGeneratorTest::class);
