<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\Presentation\Admin\Account;

use DateTimeImmutable;
use MichalSpacekCz\Test\TestCaseRunner;
use MichalSpacekCz\User\SecurityActivity\SecurityEvent;
use MichalSpacekCz\User\SecurityActivity\SecurityEventType;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../../../bootstrap.php';

/** @testCase */
final class SecurityLogTemplateParametersTest extends TestCase
{

	public function testStoresProperties(): void
	{
		$events = [new SecurityEvent(SecurityEventType::SignInSuccess, 'signin.success', new DateTimeImmutable(), '1.2.3.4', null, [])];
		$params = new SecurityLogTemplateParameters('Title', $events);
		Assert::same('Title', $params->pageTitle);
		Assert::same($events, $params->events);
	}

}

TestCaseRunner::run(SecurityLogTemplateParametersTest::class);
