<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Templating;

use MichalSpacekCz\Test\TestCaseRunner;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../bootstrap.php';

/** @testCase */

class FiltersTest extends TestCase
{

	public function __construct(
		private readonly Filters $filters,
	) {
	}


	public function testFormat(): void
	{
		Assert::same('<em>foo</em> bar 303', $this->filters->format('*foo* %s %d', 'bar', 303)->render());
	}

}

TestCaseRunner::run(FiltersTest::class);
