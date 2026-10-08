<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\Presentation\Admin\SecurityTxtValidator;

use MichalSpacekCz\Test\TestCaseRunner;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../../../bootstrap.php';

/** @testCase */
final class SecurityTxtValidatorDefaultTemplateParametersTest extends TestCase
{

	public function testStoresProperties(): void
	{
		$params = new SecurityTxtValidatorDefaultTemplateParameters('Title');
		Assert::same('Title', $params->pageTitle);
	}

}

TestCaseRunner::run(SecurityTxtValidatorDefaultTemplateParametersTest::class);
