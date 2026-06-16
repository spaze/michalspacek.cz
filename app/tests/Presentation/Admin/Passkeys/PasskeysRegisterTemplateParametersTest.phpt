<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Presentation\Admin\Passkeys;

use MichalSpacekCz\Test\TestCaseRunner;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../../../bootstrap.php';

/** @testCase */
final class PasskeysRegisterTemplateParametersTest extends TestCase
{

	public function testStoresProperties(): void
	{
		$params = new PasskeysRegisterTemplateParameters('Title', true);
		Assert::same('Title', $params->pageTitle);
		Assert::true($params->registrationEnabled);
	}

}

TestCaseRunner::run(PasskeysRegisterTemplateParametersTest::class);
