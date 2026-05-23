<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Presentation\Admin\Passkeys;

use MichalSpacekCz\Test\TestCaseRunner;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../../../bootstrap.php';

/** @testCase */
final class PasskeysRenameTemplateParametersTest extends TestCase
{

	public function testStoresProperties(): void
	{
		$params = new PasskeysRenameTemplateParameters('Title', 'passkey-name');
		Assert::same('Title', $params->pageTitle);
		Assert::same('passkey-name', $params->name);
	}

}

TestCaseRunner::run(PasskeysRenameTemplateParametersTest::class);
