<?php
/** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection PhpDynamicFieldDeclarationInspection */
/** @noinspection PhpUndefinedFieldInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\User;

use MichalSpacekCz\Application\LinkGenerator;
use MichalSpacekCz\Database\TypedDatabase;
use MichalSpacekCz\Http\Cookies\CookieName;
use MichalSpacekCz\Http\Cookies\Cookies;
use MichalSpacekCz\Test\Database\Database;
use MichalSpacekCz\Test\Http\Request;
use MichalSpacekCz\Test\PrivateProperty;
use MichalSpacekCz\Test\TestCaseRunner;
use MichalSpacekCz\User\Exceptions\IdentityNotSimpleIdentityException;
use MichalSpacekCz\User\Exceptions\IdentityUsernameNotStringException;
use MichalSpacekCz\User\Exceptions\IdentityWithoutUsernameException;
use MichalSpacekCz\User\WebAuthn\Exceptions\PasskeyResetDisabledException;
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
		private readonly Cookies $cookies,
		private readonly LinkGenerator $linkGenerator,
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
		$identity = $this->getAuthenticator(false)->getIdentity($id, $username);
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
			$this->getAuthenticator(false)->getIdentityUsernameByUser($this->user);
		}, IdentityNotSimpleIdentityException::class, 'Identity is of class <null> but should be Nette\Security\SimpleIdentity');
	}


	public function testGetIdentityUsernameByUserNoUsername(): void
	{
		PrivateProperty::setValue($this->user, 'authenticated', true);
		PrivateProperty::setValue($this->user, 'identity', new SimpleIdentity(1337));
		Assert::exception(function (): void {
			$this->getAuthenticator(false)->getIdentityUsernameByUser($this->user);
		}, IdentityWithoutUsernameException::class);
	}


	public function testGetIdentityUsernameByUserUsernameNotString(): void
	{
		PrivateProperty::setValue($this->user, 'authenticated', true);
		PrivateProperty::setValue($this->user, 'identity', new SimpleIdentity(1337, [], ['username' => 303]));
		Assert::exception(function (): void {
			$this->getAuthenticator(false)->getIdentityUsernameByUser($this->user);
		}, IdentityUsernameNotStringException::class, 'Identity username is of type int, not a string');
	}


	public function testGetIdentityUsernameByUser(): void
	{
		$id = 1337;
		$username = 'pizza';
		$authenticator = $this->getAuthenticator(false);
		$identity = $authenticator->getIdentity($id, $username);
		PrivateProperty::setValue($this->user, 'authenticated', true);
		PrivateProperty::setValue($this->user, 'identity', $identity);
		Assert::same($username, $authenticator->getIdentityUsernameByUser($this->user));
	}


	public function testIsResetEnabled(): void
	{
		Assert::false($this->getAuthenticator(false)->isPasskeyResetEnabled());
		Assert::true($this->getAuthenticator(true)->isPasskeyResetEnabled());
		Assert::false($this->getAuthenticator(false)->isPasskeyResetEnabled());
	}


	public function testCreateResetTokenThrowsWhenDisabled(): void
	{
		Assert::exception(function (): void {
			$this->getAuthenticator(false)->createPasskeyResetToken(1337);
		}, PasskeyResetDisabledException::class);
	}


	public function testVerifyResetTokenThrowsWhenDisabled(): void
	{
		Assert::exception(function (): void {
			$this->getAuthenticator(false)->verifyPasskeyResetToken('some token');
		}, PasskeyResetDisabledException::class);
	}


	public function testVerifyPermanentLogin(): void
	{
		$authenticator = $this->getAuthenticator(false);
		Assert::null($authenticator->verifyPermanentLogin());

		$tokenId = 1337;
		$token = 'bar';
		$hash = hash('sha512', $token);
		$userId = 1338;
		$username = '🍪🍪🍪';
		$this->database->setFetchDefaultResult([
			'id' => $tokenId,
			'token' => $hash,
			'userId' => $userId,
			'username' => $username,
		]);
		$this->httpRequest->setCookie(CookieName::PermanentLogin->value, "foo:{$token}");
		$authToken = $authenticator->verifyPermanentLogin();
		if (!$authToken instanceof UserAuthToken) {
			Assert::fail('Token is of a wrong type ' . get_debug_type($authToken));
		} else {
			Assert::same($tokenId, $authToken->getId());
			Assert::same($hash, $authToken->getToken());
			Assert::same($userId, $authToken->getUserId());
			Assert::same($username, $authToken->getUsername());
		}

		$this->httpRequest->setCookie(CookieName::PermanentLogin->value, "foo:not{$token}");
		Assert::null($authenticator->verifyPermanentLogin());
	}


	private function getAuthenticator(bool $resetEnabled): Manager
	{
		return new Manager(
			$this->database,
			$this->typedDatabase,
			$this->httpRequest,
			$this->cookies,
			$this->linkGenerator,
			'14 days',
			$resetEnabled,
			'users',
		);
	}

}

TestCaseRunner::run(ManagerTest::class);
