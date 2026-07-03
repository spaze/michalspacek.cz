<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\User\PermanentLogin;

use MichalSpacekCz\Application\LinkGenerator;
use MichalSpacekCz\Database\TypedDatabase;
use MichalSpacekCz\DateTime\DateTimeFactory;
use MichalSpacekCz\Http\Cookies\CookieName;
use MichalSpacekCz\Http\Cookies\Cookies;
use MichalSpacekCz\Test\Database\Database;
use MichalSpacekCz\Test\Database\ResultSet;
use MichalSpacekCz\Test\Http\Request;
use MichalSpacekCz\Test\Http\Response;
use MichalSpacekCz\Test\TestCaseRunner;
use MichalSpacekCz\User\AuthTokens\UserAuthToken;
use MichalSpacekCz\User\AuthTokens\UserAuthTokens;
use MichalSpacekCz\User\AuthTokens\UserAuthTokenType;
use MichalSpacekCz\User\Manager;
use MichalSpacekCz\User\SecurityActivity\SecurityEventLogger;
use MichalSpacekCz\User\SecurityActivity\SecurityEventType;
use Nette\Security\SimpleIdentity;
use Nette\Security\User;
use Override;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../../bootstrap.php';

/** @testCase */
final class PermanentLoginTest extends TestCase
{

	public function __construct(
		private readonly Database $database,
		private readonly TypedDatabase $typedDatabase,
		private readonly Request $httpRequest,
		private readonly Response $httpResponse,
		private readonly Cookies $cookies,
		private readonly DateTimeFactory $dateTimeFactory,
		private readonly LinkGenerator $linkGenerator,
		private readonly User $user,
		private readonly SecurityEventLogger $securityEventLogger,
	) {
	}


	#[Override]
	protected function tearDown(): void
	{
		$this->database->reset();
		$this->httpResponse->reset();
		$this->user->logout();
	}


	public function testVerify(): void
	{
		$permanentLogin = $this->getPermanentLogin();
		Assert::null($permanentLogin->verify());

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
		$authToken = $permanentLogin->verify();
		if (!$authToken instanceof UserAuthToken) {
			Assert::fail('Token is of a wrong type ' . get_debug_type($authToken));
		} else {
			Assert::same($tokenId, $authToken->getId());
			Assert::same($hash, $authToken->getToken());
			Assert::same($userId, $authToken->getUserId());
			Assert::same($username, $authToken->getUsername());
		}

		$this->httpRequest->setCookie(CookieName::PermanentLogin->value, "foo:not{$token}");
		Assert::null($permanentLogin->verify());
	}


	public function testSignInWithoutValidTokenDoesNothing(): void
	{
		Assert::false($this->getPermanentLogin()->signIn());
		Assert::false($this->user->isLoggedIn());
		Assert::count(0, $this->database->getParamsArrayForQuery('INSERT INTO security_events'));
	}


	public function testSignInLogsInRotatesAndRecordsEvent(): void
	{
		$token = 'sometoken';
		$this->database->setFetchDefaultResult([
			'id' => 1,
			'token' => hash('sha512', $token),
			'userId' => 42,
			'username' => 'spaze',
		]);
		$this->httpRequest->setCookie(CookieName::PermanentLogin->value, "selector:{$token}");

		Assert::true($this->getPermanentLogin()->signIn());
		Assert::same(42, $this->user->getId());

		$inserts = $this->database->getParamsArrayForQuery('INSERT INTO security_events');
		Assert::count(1, $inserts);
		Assert::same(SecurityEventType::SignInPermanent->value, $inserts[0]['action']);
		Assert::same(42, $inserts[0]['key_user']);
	}


	public function testGetCookieLifetime(): void
	{
		Assert::same('14 days', $this->getPermanentLogin()->getCookieLifetime());
	}


	public function testDeleteExpiredQueriesPermanentLoginType(): void
	{
		$this->database->setResultSet(new ResultSet(3));
		$deleted = $this->getPermanentLogin()->deleteExpired();

		Assert::same(3, $deleted);
		$params = $this->database->getParamsForQuery('DELETE FROM auth_tokens WHERE type = ? AND created <= ?');
		Assert::same(UserAuthTokenType::PermanentLogin->value, $params[0]);
		Assert::type('string', $params[1]); // DateTime formatted by Database mock
	}


	public function testClearDeletesTokensAndCookie(): void
	{
		$userId = 1337;
		$this->user->login(new SimpleIdentity($userId));
		$this->cookies->set(CookieName::PermanentLogin, 'goodbye', '14 days');
		$this->getPermanentLogin()->clear();

		Assert::same(
			[$userId, UserAuthTokenType::PermanentLogin->value],
			$this->database->getParamsForQuery('DELETE FROM auth_tokens WHERE key_user = ? AND type = ?'),
		);
		Assert::count(0, $this->httpResponse->getCookie(CookieName::PermanentLogin->value));
	}


	private function getPermanentLogin(): PermanentLogin
	{
		$manager = new Manager($this->typedDatabase, $this->httpRequest, 'users');
		$tokens = new UserAuthTokens($this->database, 'users');
		return new PermanentLogin($tokens, $this->cookies, $manager, $this->user, $this->securityEventLogger, $this->dateTimeFactory, $this->linkGenerator, '14 days');
	}

}

TestCaseRunner::run(PermanentLoginTest::class);
