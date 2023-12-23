<?php
declare(strict_types = 1);

namespace MichalSpacekCz\User;

use DateTimeInterface;
use Exception;
use MichalSpacekCz\Http\Cookies\CookieName;
use MichalSpacekCz\Http\Cookies\Cookies;
use MichalSpacekCz\User\Exceptions\IdentityException;
use MichalSpacekCz\User\Exceptions\IdentityIdNotIntException;
use MichalSpacekCz\User\Exceptions\IdentityNotSimpleIdentityException;
use MichalSpacekCz\User\Exceptions\IdentityUsernameNotStringException;
use MichalSpacekCz\User\Exceptions\IdentityWithoutUsernameException;
use Nette\Application\LinkGenerator;
use Nette\Database\Explorer;
use Nette\Database\Row;
use Nette\Database\UniqueConstraintViolationException;
use Nette\Http\IRequest;
use Nette\Http\Url;
use Nette\Security\AuthenticationException;
use Nette\Security\Authenticator;
use Nette\Security\IIdentity;
use Nette\Security\Passwords;
use Nette\Security\SimpleIdentity;
use Nette\Security\User;
use Nette\Utils\DateTime;
use Nette\Utils\Random;
use ParagonIE\Halite\Alerts\HaliteAlert;
use Spaze\Encryption\Symmetric\StaticKey;
use Tracy\Debugger;

class Manager implements Authenticator
{

	private const AUTH_SELECTOR_TOKEN_SEPARATOR = ':';

	private const TOKEN_PERMANENT_LOGIN = 1;

	private const TOKEN_RETURNING_USER = 2;

	private ?string $authCookiesPath = null;


	public function __construct(
		private readonly Explorer $database,
		private readonly IRequest $httpRequest,
		private readonly Cookies $cookies,
		private readonly Passwords $passwords,
		private readonly StaticKey $passwordEncryption,
		LinkGenerator $linkGenerator,
		private readonly string $permanentLoginInterval,
	) {
		$this->authCookiesPath = (new Url($linkGenerator->link('Admin:Sign:in')))->getPath();
	}


	/**
	 * Performs an authentication.
	 *
	 * @param string $user
	 * @param string $password
	 * @return IIdentity
	 * @throws AuthenticationException
	 */
	public function authenticate(string $user, string $password): IIdentity
	{
		$userId = $this->verifyPassword($user, $password);
		return $this->getIdentity($userId, $user);
	}


	/**
	 * Get identity object.
	 *
	 * @param int $id User id
	 * @param string $username Username
	 * @return SimpleIdentity
	 */
	public function getIdentity(int $id, string $username): SimpleIdentity
	{
		return new SimpleIdentity($id, [], ['username' => $username]);
	}


	/**
	 * @throws IdentityNotSimpleIdentityException
	 * @throws IdentityWithoutUsernameException
	 * @throws IdentityUsernameNotStringException
	 */
	public function getIdentityUsernameByUser(User $user): string
	{
		$identity = $user->getIdentity();
		if (!$identity instanceof SimpleIdentity) {
			throw new IdentityNotSimpleIdentityException($identity);
		}
		if (!isset($identity->username)) {
			throw new IdentityWithoutUsernameException();
		}
		if (!is_string($identity->username)) {
			throw new IdentityUsernameNotStringException(get_debug_type($identity->username));
		}
		return $identity->username;
	}


	/**
	 * @return int User id
	 * @throws AuthenticationException
	 */
	private function verifyPassword(string $username, string $password): int
	{
		$user = $this->database->fetch(
			'SELECT
				id_user AS userId,
				username,
				password
			FROM
				users
			WHERE
				username = ?',
			$username,
		);
		if (!$user) {
			throw new AuthenticationException('The username is incorrect.', self::IdentityNotFound);
		}
		try {
			$hash = $this->passwordEncryption->decrypt((string)$user->password);
			if (!$this->passwords->verify($password, $hash)) {
				throw new AuthenticationException('The password is incorrect.', self::InvalidCredential);
			} elseif ($this->passwords->needsRehash($hash)) {
				$this->updatePassword($user->userId, $password);
			}
		} catch (HaliteAlert $e) {
			Debugger::log($e);
			throw new AuthenticationException('Oops... Something went wrong.', self::Failure);
		}
		return $user->userId;
	}


	/**
	 * @param User $user
	 * @param string $password
	 * @param string $newPassword
	 * @throws AuthenticationException
	 * @throws HaliteAlert
	 * @throws IdentityException
	 */
	public function changePassword(User $user, string $password, string $newPassword): void
	{
		$userId = $user->getId();
		if (!is_int($userId)) {
			throw new IdentityIdNotIntException(get_debug_type($userId));
		}
		$this->verifyPassword($this->getIdentityUsernameByUser($user), $password);
		$this->updatePassword($userId, $newPassword);
		$this->clearPermanentLogin($user);
	}


	/**
	 * @throws HaliteAlert
	 */
	private function updatePassword(int $userId, string $newPassword): void
	{
		$encrypted = $this->passwordEncryption->encrypt($this->passwords->hash($newPassword));
		$this->database->query('UPDATE users SET password = ? WHERE id_user = ?', $encrypted, $userId);
	}


	public function isForbidden(): bool
	{
		$forbidden = $this->database->fetchField(
			'SELECT
				1
			FROM
				forbidden
			WHERE
				ip = ?',
			$this->httpRequest->getRemoteAddress(),
		);
		return (bool)$forbidden;
	}


	public function setReturningUser(string $value): void
	{
		$this->cookies->set(CookieName::ReturningUser, $value, $this->getReturningUserCookieLifetime(), $this->authCookiesPath, sameSite: 'Strict');
	}


	public function isReturningUser(): bool
	{
		$cookie = $this->cookies->getString(CookieName::ReturningUser);
		return ($cookie && $this->verifyReturningUser($cookie));
	}


	/**
	 * Hash token used for permanent login.
	 *
	 * @param string $token
	 * @return non-empty-string SHA-512 hash of the token
	 */
	private function hashToken(string $token): string
	{
		return hash('sha512', $token);
	}


	/**
	 * Insert authentication token into database.
	 *
	 * Selector and token are regenerated if selector already exists in the table.
	 *
	 * @param User $user
	 * @param int $type
	 * @return non-empty-string Concatenation of selector, separator, token
	 * @throws Exception
	 */
	private function insertToken(User $user, int $type): string
	{
		$selector = Random::generate(32, '0-9a-zA-Z');
		$token = Random::generate(64, '0-9a-zA-Z');

		try {
			$this->database->query(
				'INSERT INTO auth_tokens',
				[
					'key_user' => $user->getId(),
					'selector' => $selector,
					'token' => $this->hashToken($token),
					'created' => new DateTime(),
					'type' => $type,
				],
			);
		} catch (UniqueConstraintViolationException) {
			// regenerate the access code and try harder this time
			return $this->insertToken($user, $type);
		}
		return $selector . self::AUTH_SELECTOR_TOKEN_SEPARATOR . $token;
	}


	/**
	 * Store permanent login token in database and send a cookie to the browser.
	 *
	 * @param User $user
	 * @throws Exception
	 */
	public function storePermanentLogin(User $user): void
	{
		$value = $this->insertToken($user, self::TOKEN_PERMANENT_LOGIN);
		$this->cookies->set(CookieName::PermanentLogin, $value, $this->permanentLoginInterval, $this->authCookiesPath, sameSite: 'Strict');
	}


	/**
	 * Delete all permanent login tokens and delete the cookie in the browser.
	 *
	 * @param User $user
	 */
	public function clearPermanentLogin(User $user): void
	{
		$this->database->query('DELETE FROM auth_tokens WHERE key_user = ? AND type = ?', $user->getId(), self::TOKEN_PERMANENT_LOGIN);
		$this->cookies->delete(CookieName::PermanentLogin, $this->authCookiesPath);
	}


	/**
	 * Regenerate permanent login token.
	 *
	 * @param User $user
	 * @throws Exception
	 */
	public function regeneratePermanentLogin(User $user): void
	{
		$this->database->beginTransaction();
		$this->database->query('DELETE FROM auth_tokens WHERE key_user = ? AND type = ?', $user->getId(), self::TOKEN_PERMANENT_LOGIN);
		$this->storePermanentLogin($user);
		$this->database->commit();
	}


	/**
	 * Verify and return permanent token, if present, and valid.
	 */
	public function verifyPermanentLogin(): ?Row
	{
		$cookie = $this->cookies->getString(CookieName::PermanentLogin) ?? '';
		return $this->verifyToken($cookie, DateTime::from("-{$this->permanentLoginInterval}"), self::TOKEN_PERMANENT_LOGIN);
	}


	/**
	 * Verify returning user, if present, and valid.
	 */
	public function verifyReturningUser(string $value): ?Row
	{
		return $this->verifyToken($value, DateTime::fromParts(2000, 1, 1), self::TOKEN_RETURNING_USER);
	}


	/**
	 * Regenerate returning user token.
	 *
	 * @throws Exception
	 */
	public function regenerateReturningUser(User $user): string
	{
		$this->database->beginTransaction();
		$this->database->query('DELETE FROM auth_tokens WHERE key_user = ? AND type = ?', $user->getId(), self::TOKEN_RETURNING_USER);
		$selectorToken = $this->insertToken($user, self::TOKEN_RETURNING_USER);
		$this->setReturningUser($selectorToken);
		$this->database->commit();
		return $selectorToken;
	}


	/**
	 * Verify and return any token, if present, and valid.
	 */
	private function verifyToken(string $value, DateTimeInterface $validity, int $type): ?Row
	{
		$result = null;
		$values = explode(self::AUTH_SELECTOR_TOKEN_SEPARATOR, $value);
		if (count($values) === 2) {
			$storedToken = $this->database->fetch(
				'SELECT
					at.id_auth_token AS tokenId,
					at.token,
					u.id_user AS userId,
					u.username
				FROM
					auth_tokens at
					JOIN users u ON u.id_user = at.key_user
				WHERE
					at.selector = ?
					AND at.created > ?
					AND type = ?',
				$values[0],
				$validity,
				$type,
			);
			if ($storedToken && hash_equals($storedToken->token, $this->hashToken($values[1]))) {
				$result = $storedToken;
			}
		}
		return $result;
	}


	public function getPermanentLoginCookieLifetime(): string
	{
		return $this->permanentLoginInterval;
	}


	public function getReturningUserCookieLifetime(): string
	{
		return '365 days';
	}

}
