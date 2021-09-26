<?php
declare(strict_types = 1);

namespace MichalSpacekCz\User;

use DateTimeInterface;
use Exception;
use Nette\Application\LinkGenerator;
use Nette\Database\Drivers\MySqlDriver;
use Nette\Database\Explorer;
use Nette\Database\Row;
use Nette\Http\IRequest;
use Nette\Http\Response;
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
use PDOException;
use Spaze\Encryption\Symmetric\StaticKey;
use Tracy\Debugger;

class Manager implements Authenticator
{

	private const AUTH_SELECTOR_TOKEN_SEPARATOR = ':';

	private const TOKEN_PERMANENT_LOGIN = 1;

	private const TOKEN_RETURNING_USER = 2;

	private Explorer $database;

	private IRequest $httpRequest;

	private Response $httpResponse;

	private Passwords $passwords;

	private StaticKey $passwordEncryption;

	private string $returningUserCookie;

	private string $permanentLoginCookie;

	private string $permanentLoginInterval;

	private ?string $authCookiesPath = null;


	public function __construct(
		Explorer $context,
		IRequest $httpRequest,
		Response $httpResponse,
		Passwords $passwords,
		StaticKey $passwordEncryption,
		LinkGenerator $linkGenerator
	) {
		$this->database = $context;
		$this->httpRequest = $httpRequest;
		$this->httpResponse = $httpResponse;
		$this->passwords = $passwords;
		$this->passwordEncryption = $passwordEncryption;
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
		return new SimpleIdentity($id, array(), array('username' => $username));
	}


	/**
	 * @param string $username
	 * @param string $password
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
			$username
		);
		if (!$user) {
			throw new AuthenticationException('The username is incorrect.', self::IDENTITY_NOT_FOUND);
		}
		$userId = (int)$user->userId;
		try {
			$hash = $this->passwordEncryption->decrypt((string)$user->password);
			if (!$this->passwords->verify($password, $hash)) {
				throw new AuthenticationException('The password is incorrect.', self::INVALID_CREDENTIAL);
			} elseif ($this->passwords->needsRehash($hash)) {
				$this->updatePassword($userId, $password);
			}
		} catch (HaliteAlert $e) {
			Debugger::log($e);
			throw new AuthenticationException('Oops... Something went wrong.', self::FAILURE);
		}
		return $userId;
	}


	/**
	 * @param User $user
	 * @param string $password
	 * @param string $newPassword
	 * @throws AuthenticationException
	 * @throws HaliteAlert
	 */
	public function changePassword(User $user, string $password, string $newPassword): void
	{
		/** @var SimpleIdentity $identity */
		$identity = $user->getIdentity();
		$this->verifyPassword($identity->username, $password);
		$this->updatePassword($user->getId(), $newPassword);
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
			$this->httpRequest->getRemoteAddress()
		);
		return (bool)$forbidden;
	}


	public function setReturningUser(string $value): void
	{
		$this->httpResponse->setCookie($this->returningUserCookie, $value, '+10 years', $this->authCookiesPath, null, null, null, 'Strict');
	}


	public function isReturningUser(): bool
	{
		$cookie = $this->httpRequest->getCookie($this->returningUserCookie);
		return ($cookie && $this->verifyReturningUser($cookie));
	}


	public function setReturningUserCookie(string $cookie): void
	{
		$this->returningUserCookie = $cookie;
	}


	public function setPermanentLoginCookie(string $cookie): void
	{
		$this->permanentLoginCookie = $cookie;
	}


	/**
	 * Set permanent login interval.
	 *
	 * @param string $interval
	 */
	public function setPermanentLoginInterval(string $interval): void
	{
		$this->permanentLoginInterval = $interval;
	}


	/**
	 * Hash token used for permanent login.
	 *
	 * @param string $token
	 * @return string SHA-512 hash of the token
	 */
	private function hashToken($token)
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
	 * @return string Concatenation of selector, separator, token
	 * @throws Exception
	 */
	private function insertToken(User $user, int $type)
	{
		$selector = Random::generate(32, '0-9a-zA-Z');
		$token = Random::generate(64, '0-9a-zA-Z');

		try {
			$this->database->query(
				'INSERT INTO auth_tokens',
				array(
					'key_user' => $user->getId(),
					'selector' => $selector,
					'token' => $this->hashToken($token),
					'created' => new DateTime(),
					'type' => $type,
				)
			);
		} catch (PDOException $e) {
			if ($e->getCode() == '23000') {
				if ($e->errorInfo[1] == MySqlDriver::ERROR_DUPLICATE_ENTRY) {
					// regenerate the access code and try harder this time
					return $this->insertToken($user, $type);
				}
			}
			throw $e;
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
		$this->httpResponse->setCookie($this->permanentLoginCookie, $value, $this->permanentLoginInterval, $this->authCookiesPath, null, null, null, 'Strict');
	}


	/**
	 * Delete all permanent login tokens and delete the cookie in the browser.
	 *
	 * @param User $user
	 */
	public function clearPermanentLogin(User $user): void
	{
		$this->database->query('DELETE FROM auth_tokens WHERE key_user = ? AND type = ?', $user->getId(), self::TOKEN_PERMANENT_LOGIN);
		$this->httpResponse->deleteCookie($this->permanentLoginCookie, $this->authCookiesPath);
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
	 *
	 * @return Row<mixed>|null
	 */
	public function verifyPermanentLogin(): ?Row
	{
		$cookie = $this->httpRequest->getCookie($this->permanentLoginCookie) ?? '';
		return $this->verifyToken($cookie, DateTime::from("-{$this->permanentLoginInterval}"), self::TOKEN_PERMANENT_LOGIN);
	}


	/**
	 * Verify returning user, if present, and valid.
	 *
	 * @param string $value
	 * @return Row<mixed>|null
	 */
	public function verifyReturningUser(string $value): ?Row
	{
		return $this->verifyToken($value, DateTime::fromParts(2000, 1, 1), self::TOKEN_RETURNING_USER);
	}


	/**
	 * Regenerate returning user token.
	 *
	 * @param User $user
	 * @throws Exception
	 * @return string
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
	 *
	 * @param string $value
	 * @param DateTimeInterface $validity
	 * @param int $type
	 * @return Row<mixed>|null
	 */
	private function verifyToken(string $value, DateTimeInterface $validity, int $type): ?Row
	{
		$result = null;
		$values = explode(self::AUTH_SELECTOR_TOKEN_SEPARATOR, $value);
		if (count($values) === 2) {
			/** @var Row<mixed>|null $storedToken */
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
				$type
			);
			if ($storedToken && hash_equals($storedToken->token, $this->hashToken($values[1]))) {
				$result = $storedToken;
			}
		}
		return $result;
	}

}
