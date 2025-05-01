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
final class SecurityTxtIssueTest extends TestCase
{

	public function testGetters(): void
	{
		$issue = new SecurityTxtIssue(
			Html::el('em')->setText('Message'),
			Html::el()->setText('How to fix'),
			'Correct value',
			null,
		);
		Assert::same('<em>Message</em>', $issue->getMessage()->render());
		Assert::same('How to fix', $issue->getHowToFix()->render());
		Assert::same('Correct value', $issue->getCorrectValue());
		Assert::null($issue->getSpecSection());
	}

}

TestCaseRunner::run(SecurityTxtIssueTest::class);
