<?php
declare(strict_types = 1);

namespace MichalSpacekCz\DateTime;

use MichalSpacekCz\Test\TestCaseRunner;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../bootstrap.php';

/** @testCase */
class DateTimeTest extends TestCase
{

	public function __construct(
		private readonly DateTime $dateTime,
	) {
	}


	public function testGetDaysFromString(): void
	{
		Assert::same(303, $this->dateTime->getDaysFromString('303 days'));
		Assert::same(14, $this->dateTime->getDaysFromString('+14 days'));
		Assert::same(0, $this->dateTime->getDaysFromString('now'));
		Assert::same(0, $this->dateTime->getDaysFromString('0 days'));
		Assert::same(0, $this->dateTime->getDaysFromString('0'));
	}

}

TestCaseRunner::run(DateTimeTest::class);
