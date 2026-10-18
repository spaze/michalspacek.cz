<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\SecurityTxtValidator\Issue;

use MichalSpacekCz\Test\TestCaseRunner;
use Nette\Utils\Html;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../../bootstrap.php';

/** @testCase */
final class SecurityTxtLineIssueTest extends TestCase
{

	public function testGetters(): void
	{
		$issue = new SecurityTxtIssue(
			Html::el('em')->setText('Message'),
			Html::el()->setText('How to fix'),
			null,
			null,
		);
		$lineContents = Html::el()->setText('Line contents');
		$lineIssue = new SecurityTxtLineIssue(SecurityTxtIssueLevel::Error, $issue, $lineContents);
		Assert::same(SecurityTxtIssueLevel::Error, $lineIssue->getLevel());
		Assert::same($issue, $lineIssue->getIssue());
		Assert::same($lineContents, $lineIssue->getLineContents());
	}

}

TestCaseRunner::run(SecurityTxtLineIssueTest::class);
