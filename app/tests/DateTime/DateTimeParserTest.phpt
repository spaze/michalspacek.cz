<?php
declare(strict_types = 1);

namespace MichalSpacekCz\DateTime;

use MichalSpacekCz\Test\TestCaseRunner;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../bootstrap.php';

/** @testCase */
class DateTimeParserTest extends TestCase
{

	public function __construct(
		private readonly DateTimeParser $dateTimeParser,
	) {
	}


	public function testGetDaysFromString(): void
	{
		Assert::same(303, $this->dateTimeParser->getDaysFromString('303 days'));
		Assert::same(14, $this->dateTimeParser->getDaysFromString('+14 days'));
		Assert::same(0, $this->dateTimeParser->getDaysFromString('now'));
		Assert::same(0, $this->dateTimeParser->getDaysFromString('0 days'));
		Assert::same(0, $this->dateTimeParser->getDaysFromString('0'));
	}

}

TestCaseRunner::run(DateTimeParserTest::class);
