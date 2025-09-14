<?php
/** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection PhpDynamicFieldDeclarationInspection */
/** @noinspection PhpUndefinedFieldInspection */
declare(strict_types = 1);

namespace User;

use MichalSpacekCz\Test\PrivateProperty;
use MichalSpacekCz\Test\Security\NullUserStorage;
use MichalSpacekCz\Test\TestCaseRunner;
use MichalSpacekCz\User\UserSessionAdditionalData;
use Nette\Security\SimpleIdentity;
use Nette\Security\User;
use Override;
use Spaze\Session\MysqlSessionHandler;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../bootstrap.php';

/** @testCase */
final class UserSessionAdditionalDataTest extends TestCase
{

	public function __construct(
		private readonly UserSessionAdditionalData $userSessionAdditionalData,
		private readonly User $user,
		private readonly MysqlSessionHandler $sessionHandler,
		private readonly NullUserStorage $userStorage,
	) {
	}


	#[Override]
	protected function tearDown(): void
	{
		$this->user->refreshStorage();
		$this->userStorage->clearAuthentication(true);
		PrivateProperty::setValue($this->sessionHandler, 'additionalData', []);
	}


	public function testInit(): void
	{
		$this->userSessionAdditionalData->init();
		Assert::same([], PrivateProperty::getValue($this->sessionHandler, 'additionalData'));
	}


	public function testInitOnLoggedInOnLoggedOut(): void
	{
		$this->userSessionAdditionalData->init();
		$this->user->login(new SimpleIdentity(1337));
		Assert::same(['key_user' => 1337], PrivateProperty::getValue($this->sessionHandler, 'additionalData'));

		$this->user->logout();
		Assert::same(['key_user' => null], PrivateProperty::getValue($this->sessionHandler, 'additionalData'));
	}


	public function testInitAlreadyLoggedIn(): void
	{
		PrivateProperty::setValue($this->user, 'identity', new SimpleIdentity(1336));
		PrivateProperty::setValue($this->user, 'authenticated', true);
		$this->userSessionAdditionalData->init();
		Assert::same(['key_user' => 1336], PrivateProperty::getValue($this->sessionHandler, 'additionalData'));
	}

}

TestCaseRunner::run(UserSessionAdditionalDataTest::class);
