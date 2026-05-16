<?php
/** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection PhpDynamicFieldDeclarationInspection */
/** @noinspection PhpUndefinedFieldInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\User;

use MichalSpacekCz\Database\TypedDatabase;
use MichalSpacekCz\Test\Database\Database;
use MichalSpacekCz\Test\Http\Request;
use MichalSpacekCz\Test\PrivateProperty;
use MichalSpacekCz\Test\TestCaseRunner;
use MichalSpacekCz\User\Exceptions\IdentityNotSimpleIdentityException;
use MichalSpacekCz\User\Exceptions\IdentityUsernameNotStringException;
use MichalSpacekCz\User\Exceptions\IdentityWithoutUsernameException;
use Nette\Security\SimpleIdentity;
use Nette\Security\User;
use Override;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../bootstrap.php';

/** @testCase */
final class ManagerTest extends TestCase
{

	public function __construct(
		private readonly User $user,
		private readonly Database $database,
		private readonly TypedDatabase $typedDatabase,
		private readonly Request $httpRequest,
	) {
	}


	#[Override]
	protected function tearDown(): void
	{
		$this->user->refreshStorage();
		$this->database->reset();
	}


	public function testGetIdentity(): void
	{
		$id = 1337;
		$username = 'pizza';
		$identity = $this->getManager()->getIdentity($id, $username);
		Assert::type(SimpleIdentity::class, $identity);
		Assert::same($id, $identity->id);
		Assert::same($id, $identity->getId());
		if (!isset($identity->username)) {
			Assert::fail('The username property should be set');
		} else {
			Assert::same($username, $identity->username);
		}
		Assert::same($username, $identity->getData()['username']);
	}


	public function testGetIdentityUsernameByUserNoIdentity(): void
	{
		Assert::exception(function (): void {
			$this->getManager()->getIdentityUsernameByUser($this->user);
		}, IdentityNotSimpleIdentityException::class, 'Identity is of class <null> but should be Nette\Security\SimpleIdentity');
	}


	public function testGetIdentityUsernameByUserNoUsername(): void
	{
		PrivateProperty::setValue($this->user, 'authenticated', true);
		PrivateProperty::setValue($this->user, 'identity', new SimpleIdentity(1337));
		Assert::exception(function (): void {
			$this->getManager()->getIdentityUsernameByUser($this->user);
		}, IdentityWithoutUsernameException::class);
	}


	public function testGetIdentityUsernameByUserUsernameNotString(): void
	{
		PrivateProperty::setValue($this->user, 'authenticated', true);
		PrivateProperty::setValue($this->user, 'identity', new SimpleIdentity(1337, [], ['username' => 303]));
		Assert::exception(function (): void {
			$this->getManager()->getIdentityUsernameByUser($this->user);
		}, IdentityUsernameNotStringException::class, 'Identity username is of type int, not a string');
	}


	public function testGetIdentityUsernameByUser(): void
	{
		$id = 1337;
		$username = 'pizza';
		$manager = $this->getManager();
		$identity = $manager->getIdentity($id, $username);
		PrivateProperty::setValue($this->user, 'authenticated', true);
		PrivateProperty::setValue($this->user, 'identity', $identity);
		Assert::same($username, $manager->getIdentityUsernameByUser($this->user));
	}


	private function getManager(): Manager
	{
		return new Manager(
			$this->typedDatabase,
			$this->httpRequest,
			'users',
		);
	}

}

TestCaseRunner::run(ManagerTest::class);
