<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\Presentation\Admin\Pulse;

use MichalSpacekCz\Test\TestCaseRunner;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../../../bootstrap.php';

/** @testCase */
final class PulseDefaultTemplateParametersTest extends TestCase
{

	public function testStoresProperties(): void
	{
		$params = new PulseDefaultTemplateParameters('Title');
		Assert::same('Title', $params->pageTitle);
	}

}

TestCaseRunner::run(PulseDefaultTemplateParametersTest::class);
