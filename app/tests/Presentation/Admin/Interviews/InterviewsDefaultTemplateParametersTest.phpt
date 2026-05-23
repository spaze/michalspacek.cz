<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Presentation\Admin\Interviews;

use MichalSpacekCz\Test\TestCaseRunner;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../../../bootstrap.php';

/** @testCase */
final class InterviewsDefaultTemplateParametersTest extends TestCase
{

	public function testStoresProperties(): void
	{
		$params = new InterviewsDefaultTemplateParameters('Title', []);
		Assert::same('Title', $params->pageTitle);
		Assert::same([], $params->interviews);
	}

}

TestCaseRunner::run(InterviewsDefaultTemplateParametersTest::class);
