<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\User\WebAuthn\Session;

use MichalSpacekCz\Test\TestCaseRunner;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../../../bootstrap.php';

/** @testCase */
final class PasskeySessionSectionFactoryTest extends TestCase
{

	public function __construct(
		private readonly PasskeySessionSectionFactory $factory,
	) {
	}


	public function testCreateReturnsPasskeySessionSection(): void
	{
		Assert::type(PasskeySessionSection::class, $this->factory->create());
	}

}

TestCaseRunner::run(PasskeySessionSectionFactoryTest::class);
