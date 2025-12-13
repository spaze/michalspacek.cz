<?php
declare(strict_types = 1);

namespace Utils;

use MichalSpacekCz\Test\TestCaseRunner;
use MichalSpacekCz\Utils\Strings;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../bootstrap.php';

/** @testCase */
final class StringsTest extends TestCase
{

	public function __construct(
		private readonly Strings $strings,
	) {
	}


	public function testLength(): void
	{
		Assert::same(9, $this->strings->length('ěščřžýáíé'));
	}

}

TestCaseRunner::run(StringsTest::class);
