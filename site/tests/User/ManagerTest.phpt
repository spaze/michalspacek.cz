<?php
/** @noinspection PhpDocMissingThrowsInspection */
/** @noinspection PhpFullyQualifiedNameUsageInspection */
/** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection PhpDynamicFieldDeclarationInspection */
/** @noinspection PhpUndefinedFieldInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\User;

use Nette\Security\SimpleIdentity;
use Nette\Security\User;
use Tester\Assert;
use Tester\TestCase;

$runner = require __DIR__ . '/../bootstrap.php';

/** @testCase */
class ManagerTest extends TestCase
{

	public function __construct(
		private readonly Manager $authenticator,
		private readonly User $user,
	) {
	}


	protected function tearDown(): void
	{
		$this->user->refreshStorage();
	}


	public function testGetIdentity(): void
	{
		$id = 1337;
		$username = 'pizza';
		$identity = $this->authenticator->getIdentity($id, $username);
		Assert::type(SimpleIdentity::class, $identity);
		Assert::same($id, $identity->id);
		Assert::same($id, $identity->getId());
		Assert::same($username, $identity->username);
		Assert::same($username, $identity->getData()['username']);
	}


	/**
	 * @throws \MichalSpacekCz\User\Exceptions\IdentityNotSimpleIdentityException Identity is of class <null> but should be Nette\Security\SimpleIdentity
	 */
	public function testGetIdentityUsernameByUserNoIdentity(): void
	{
		$this->authenticator->getIdentityUsernameByUser($this->user);
	}


	/**
	 * @throws \MichalSpacekCz\User\Exceptions\IdentityWithoutUsernameException
	 */
	public function testGetIdentityUsernameByUserNoUsername(): void
	{
		Assert::with($this->user, function (): void {
			$this->authenticated = true;
			$this->identity = new SimpleIdentity(1337);
		});
		$this->authenticator->getIdentityUsernameByUser($this->user);
	}


	/**
	 * @throws \MichalSpacekCz\User\Exceptions\IdentityUsernameNotStringException Identity username is of type int, not a string
	 */
	public function testGetIdentityUsernameByUserUsernameNotString(): void
	{
		Assert::with($this->user, function (): void {
			$this->authenticated = true;
			$this->identity = new SimpleIdentity(1337, [], ['username' => 303]);
		});
		$this->authenticator->getIdentityUsernameByUser($this->user);
	}


	public function testGetIdentityUsernameByUser(): void
	{
		$id = 1337;
		$username = 'pizza';
		$identity = $this->authenticator->getIdentity($id, $username);
		Assert::with($this->user, function () use ($identity): void {
			$this->authenticated = true;
			$this->identity = $identity;
		});
		Assert::same($username, $this->authenticator->getIdentityUsernameByUser($this->user));
	}


	/**
	 * @throws \MichalSpacekCz\User\Exceptions\IdentityIdNotIntException Identity id is of type string, not an integer
	 */
	public function testChangePasswordUserIdNotInt(): void
	{
		Assert::with($this->user, function (): void {
			$this->authenticated = true;
			$this->identity = new SimpleIdentity('e1337', [], ['username' => '303']);
		});
		$this->authenticator->changePassword($this->user, 'hunter2', 'hunter3');
	}

}

$runner->run(ManagerTest::class);
