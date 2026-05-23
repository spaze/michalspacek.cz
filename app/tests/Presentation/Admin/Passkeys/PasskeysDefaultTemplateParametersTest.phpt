<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\Presentation\Admin\Passkeys;

use DateTimeImmutable;
use MichalSpacekCz\Test\TestCaseRunner;
use MichalSpacekCz\User\WebAuthn\RegisteredPasskey;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../../../bootstrap.php';

/** @testCase */
final class PasskeysDefaultTemplateParametersTest extends TestCase
{

	public function testStoresProperties(): void
	{
		$now = new DateTimeImmutable();
		$passkeys = [new RegisteredPasskey('019e08b4-8b1e-77b7-bb24-3c8e4aee3444', 'key', $now, null, $now)];
		$params = new PasskeysDefaultTemplateParameters('Title', $passkeys);
		Assert::same('Title', $params->pageTitle);
		Assert::same($passkeys, $params->passkeys);
	}

}

TestCaseRunner::run(PasskeysDefaultTemplateParametersTest::class);
