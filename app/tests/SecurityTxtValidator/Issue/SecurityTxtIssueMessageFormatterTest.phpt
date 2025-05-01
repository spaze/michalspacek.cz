<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\SecurityTxtValidator\Issue;

use MichalSpacekCz\SecurityTxtValidator\Issue\SecurityTxtIssueMessageFormatter;
use MichalSpacekCz\Test\TestCaseRunner;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../../bootstrap.php';

/** @testCase */
final class SecurityTxtIssueMessageFormatterTest extends TestCase
{

	public function __construct(
		private readonly SecurityTxtIssueMessageFormatter $issueMessageFormatter,
	) {
	}


	public function testFormat(): void
	{
		$expected = 'foo <code>FOO</code> bar <code>BAR</code> baz %d waldo';
		Assert::same($expected, $this->issueMessageFormatter->format('foo %s bar %s baz %d waldo', ['FOO', 'BAR'])->render());
	}

}

TestCaseRunner::run(SecurityTxtIssueMessageFormatterTest::class);
