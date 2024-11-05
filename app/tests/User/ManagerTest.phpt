<?php
/** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection PhpDynamicFieldDeclarationInspection */
/** @noinspection PhpUndefinedFieldInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\User;

use MichalSpacekCz\Http\Cookies\CookieName;
use MichalSpacekCz\ShouldNotHappenException;
use MichalSpacekCz\Test\Database\Database;
use MichalSpacekCz\Test\Http\Request;
use MichalSpacekCz\Test\PrivateProperty;
use MichalSpacekCz\Test\TestCaseRunner;
use MichalSpacekCz\User\Exceptions\IdentityIdNotIntException;
use MichalSpacekCz\User\Exceptions\IdentityNotSimpleIdentityException;
use MichalSpacekCz\User\Exceptions\IdentityUsernameNotStringException;
use MichalSpacekCz\User\Exceptions\IdentityWithoutUsernameException;
use Nette\DI\Container;
use Nette\Security\AuthenticationException;
use Nette\Security\Passwords;
use Nette\Security\SimpleIdentity;
use Nette\Security\User;
use Override;
use PDOException;
use Spaze\Encryption\SymmetricKeyEncryption;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../bootstrap.php';

/** @testCase */
class ManagerTest extends TestCase
{

	private readonly SymmetricKeyEncryption $passwordEncryption;


	public function __construct(
		private readonly Manager $authenticator,
		private readonly User $user,
		private readonly Database $database,
		private readonly Passwords $passwords,
		private readonly Request $request,
		Container $container,
	) {
		$service = $container->getService('passwordEncryption');
		if (!$service instanceof SymmetricKeyEncryption) {
			throw new ShouldNotHappenException(sprintf('passwordEncryption should be a %s instance, but it is a %s', SymmetricKeyEncryption::class, $service::class));
		}
		$this->passwordEncryption = $service;
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
		$identity = $this->authenticator->getIdentity($id, $username);
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
			$this->authenticator->getIdentityUsernameByUser($this->user);
		}, IdentityNotSimpleIdentityException::class, 'Identity is of class <null> but should be Nette\Security\SimpleIdentity');
	}


	public function testGetIdentityUsernameByUserNoUsername(): void
	{
		PrivateProperty::setValue($this->user, 'authenticated', true);
		PrivateProperty::setValue($this->user, 'identity', new SimpleIdentity(1337));
		Assert::exception(function (): void {
			$this->authenticator->getIdentityUsernameByUser($this->user);
		}, IdentityWithoutUsernameException::class);
	}


	public function testGetIdentityUsernameByUserUsernameNotString(): void
	{
		PrivateProperty::setValue($this->user, 'authenticated', true);
		PrivateProperty::setValue($this->user, 'identity', new SimpleIdentity(1337, [], ['username' => 303]));
		Assert::exception(function (): void {
			$this->authenticator->getIdentityUsernameByUser($this->user);
		}, IdentityUsernameNotStringException::class, 'Identity username is of type int, not a string');
	}


	public function testGetIdentityUsernameByUser(): void
	{
		$id = 1337;
		$username = 'pizza';
		$identity = $this->authenticator->getIdentity($id, $username);
		PrivateProperty::setValue($this->user, 'authenticated', true);
		PrivateProperty::setValue($this->user, 'identity', $identity);
		Assert::same($username, $this->authenticator->getIdentityUsernameByUser($this->user));
	}


	public function testChangePassword(): void
	{
		$oldPassword = 'hunter2';
		$newPassword = 'hunter3';
		PrivateProperty::setValue($this->user, 'authenticated', true);
		PrivateProperty::setValue($this->user, 'identity', new SimpleIdentity(1337, [], ['username' => '303']));
		$this->database->setFetchResult([
			'userId' => 1337,
			'username' => '303',
			'password' => $this->passwordEncryption->encrypt($this->passwords->hash($oldPassword)),
		]);
		Assert::noError(function () use ($oldPassword, $newPassword): void {
			$this->authenticator->changePassword($this->user, $oldPassword, $newPassword);
		});
		$encryptedHash = $this->database->getParamsForQuery('UPDATE users SET password = ? WHERE id_user = ?')[0];
		if (is_string($encryptedHash)) {
			Assert::true($this->passwords->verify($newPassword, $this->passwordEncryption->decrypt($encryptedHash)));
		} else {
			Assert::fail('Encrypted hash should be a string but is ' . get_debug_type($encryptedHash));
		}
	}


	public function testChangePasswordUserIdNotInt(): void
	{
		PrivateProperty::setValue($this->user, 'authenticated', true);
		PrivateProperty::setValue($this->user, 'identity', new SimpleIdentity('e1337', [], ['username' => '303']));
		$oldPassword = 'hunter2';
		$newPassword = 'hunter3';
		$e = Assert::exception(function () use ($oldPassword, $newPassword): void {
			$this->authenticator->changePassword($this->user, $oldPassword, $newPassword);
		}, IdentityIdNotIntException::class, 'Identity id is of type string, not an integer');
		if (!$e instanceof IdentityIdNotIntException) {
			Assert::fail('Exception is of a wrong type ' . get_debug_type($e));
		} else {
			Assert::notContains($oldPassword, $e->getTraceAsString());
			Assert::notContains($newPassword, $e->getTraceAsString());
		}
	}


	public function testVerifyPermanentLogin(): void
	{
		Assert::null($this->authenticator->verifyPermanentLogin());

		$tokenId = 1337;
		$token = 'bar';
		$hash = hash('sha512', $token);
		$userId = 1338;
		$username = '🍪🍪🍪';
		$this->database->setFetchResult([
			'id' => $tokenId,
			'token' => $hash,
			'userId' => $userId,
			'username' => $username,
		]);
		$this->request->setCookie(CookieName::PermanentLogin->value, "foo:{$token}");
		$authToken = $this->authenticator->verifyPermanentLogin();
		if (!$authToken instanceof UserAuthToken) {
			Assert::fail('Token is of a wrong type ' . get_debug_type($authToken));
		} else {
			Assert::same($tokenId, $authToken->getId());
			Assert::same($hash, $authToken->getToken());
			Assert::same($userId, $authToken->getUserId());
			Assert::same($username, $authToken->getUsername());
		}

		$this->request->setCookie(CookieName::PermanentLogin->value, "foo:not{$token}");
		Assert::null($this->authenticator->verifyPermanentLogin());
	}


	public function testVerifyReturningUser(): void
	{
		Assert::null($this->authenticator->verifyReturningUser('foo'));

		$tokenId = 1337;
		$token = 'baz';
		$hash = hash('sha512', $token);
		$userId = 1338;
		$username = '🍕🍕🍕';
		$this->database->setFetchResult([
			'id' => $tokenId,
			'token' => $hash,
			'userId' => $userId,
			'username' => $username,
		]);
		$authToken = $this->authenticator->verifyReturningUser("foo:{$token}");
		if (!$authToken instanceof UserAuthToken) {
			Assert::fail('Token is of a wrong type ' . get_debug_type($authToken));
		} else {
			Assert::same($tokenId, $authToken->getId());
			Assert::same($hash, $authToken->getToken());
			Assert::same($userId, $authToken->getUserId());
			Assert::same($username, $authToken->getUsername());
		}

		Assert::null($this->authenticator->verifyReturningUser("foo:not{$token}"));
	}


	public function testAuthenticate(): void
	{
		$userId = 1337;
		$username = '303';
		$password = 'hunter42';
		Assert::exception(function (): void {
			$this->authenticator->authenticate('foo', 'bar');
		}, AuthenticationException::class, 'The username is incorrect.');
		$this->database->setFetchResult([
			'userId' => $userId,
			'username' => $username,
			'password' => $this->passwordEncryption->encrypt($this->passwords->hash($password)),
		]);
		Assert::exception(function () use ($username): void {
			$this->authenticator->authenticate($username, 'bar');
		}, AuthenticationException::class, 'The password is incorrect.');
		$identity = $this->authenticator->authenticate($username, $password);
		Assert::same($userId, $identity->getId());
		Assert::same($username, $identity->getData()['username']);
	}


	public function testAuthenticateRehashPasswords(): void
	{
		$userId = 1337;
		$username = 'foo';
		$password = 'bar';
		$hash = password_hash($password, PASSWORD_DEFAULT);
		$this->database->setFetchResult([
			'userId' => $userId,
			'username' => $username,
			'password' => $this->passwordEncryption->encrypt($hash),
		]);
		Assert::noError(function () use ($username, $password): void {
			$this->authenticator->authenticate($username, $password);
		});
		$params = $this->database->getParamsForQuery('UPDATE users SET password = ? WHERE id_user = ?');
		if (!is_string($params[0])) {
			Assert::fail('Encrypted data is of a wrong type ' . get_debug_type($params[0]));
		} else {
			Assert::notSame($hash, $this->passwordEncryption->decrypt($params[0]));
		}
		if (!is_int($params[1])) {
			Assert::fail('User id is of a wrong type ' . get_debug_type($params[0]));
		} else {
			Assert::same($userId, $params[1]);
		}
	}


	public function testAuthenticateException(): void
	{
		$password = 'hunter2';
		$this->database->willThrow(new PDOException());
		$e = Assert::exception(function () use ($password): void {
			$this->authenticator->authenticate('foo', $password);
		}, AuthenticationException::class);
		if (!$e instanceof AuthenticationException) {
			Assert::fail('Exception is of a wrong type ' . get_debug_type($e));
		} else {
			Assert::notContains($password, $e->getTraceAsString());
		}
	}

}

TestCaseRunner::run(ManagerTest::class);
