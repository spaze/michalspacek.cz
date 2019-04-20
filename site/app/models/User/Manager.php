<?php
declare(strict_types = 1);

namespace MichalSpacekCz\User;

use Nette\Database\Row;
use Nette\Security\Identity;
use Nette\Security\IIdentity;
use Nette\Security\User;
use Nette\Utils\DateTime;

class Manager implements \Nette\Security\IAuthenticator
{

	private const AUTH_SELECTOR_TOKEN_SEPARATOR = ':';

	private const TOKEN_PERMANENT_LOGIN = 1;

	private const TOKEN_RETURNING_USER = 2;

	/** @var \Nette\Database\Context */
	protected $database;

	/** @var \Nette\Http\IRequest */
	protected $httpRequest;

	/** @var \Nette\Http\Response */
	protected $httpResponse;

	/** @var \Spaze\Encryption\Symmetric\StaticKey */
	protected $passwordEncryption;

	/** @var string */
	private $returningUserCookie;

	/** @var string */
	private $permanentLoginCookie;

	/** @var string */
	private $permanentLoginInterval;

	/** @var string */
	private $authCookiesPath = null;


	public function __construct(
		\Nette\Database\Context $context,
		\Nette\Http\IRequest $httpRequest,
		\Nette\Http\Response $httpResponse,
		\Spaze\Encryption\Symmetric\StaticKey $passwordEncryption,
		\Nette\Application\LinkGenerator $linkGenerator
	)
	{
		$this->database = $context;
		$this->httpRequest = $httpRequest;
		$this->httpResponse = $httpResponse;
		$this->passwordEncryption = $passwordEncryption;
		$this->authCookiesPath = (new \Nette\Http\Url($linkGenerator->link('Admin:Sign:in')))->getPath();
	}


	/**
	 * Performs an authentication.
	 *
	 * @param string[] $credentials
	 * @return IIdentity
	 *
	 * @throws \Nette\Security\AuthenticationException
	 */
	public function authenticate(array $credentials): IIdentity
	{
		list($username, $password) = $credentials;
		$userId = $this->verifyPassword($username, $password);
		return $this->getIdentity($userId, $username);
	}


	/**
	 * Get identity object.
	 *
	 * @param integer $id User id
	 * @param string $username Username
	 * @return Identity
	 */
	public function getIdentity(int $id, string $username): Identity
	{
		return new Identity($id, array(), array('username' => $username));
	}


	/**
	 * @param string $username
	 * @param string $password
	 * @return int User id
	 * @throws \Nette\Security\AuthenticationException
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
			throw new \Nette\Security\AuthenticationException('The username is incorrect.', self::IDENTITY_NOT_FOUND);
		}
		try {
			if (!$this->verifyHash($password, $this->passwordEncryption->decrypt((string)$user->password))) {
				throw new \Nette\Security\AuthenticationException('The password is incorrect.', self::INVALID_CREDENTIAL);
			}
		} catch (\ParagonIE\Halite\Alerts\HaliteAlert $e) {
			\Tracy\Debugger::log($e);
			throw new \Nette\Security\AuthenticationException('Oops... Something went wrong.', self::FAILURE);
		}
		return (int)$user->userId;
	}


	private function calculateHash(string $password): string
	{
		return password_hash($password, PASSWORD_DEFAULT);
	}


	private function verifyHash(string $password, string $hash): bool
	{
		return password_verify($password, $hash);
	}


	/**
	 * @param User $user
	 * @param string $password
	 * @param string $newPassword
	 * @throws \Nette\Security\AuthenticationException
	 * @throws \ParagonIE\Halite\Alerts\HaliteAlert
	 */
	public function changePassword(User $user, string $password, string $newPassword): void
	{
		/** @var Identity $identity */
		$identity = $user->getIdentity();
		$this->verifyPassword($identity->username, $password);
		$encrypted = $this->passwordEncryption->encrypt($this->calculateHash($newPassword));
		$this->database->query('UPDATE users SET password = ? WHERE id_user = ?', $encrypted, $user->getId());
		$this->clearPermanentLogin($user);
	}


	public function isForbidden()
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
		$this->httpResponse->setCookie($this->returningUserCookie, $value, \Nette\Http\Response::PERMANENT, $this->authCookiesPath, null, null, null, 'Strict');
	}


	public function isReturningUser(): bool
	{
		$cookie = $this->httpRequest->getCookie($this->returningUserCookie);
		return ($cookie && $this->verifyReturningUser($cookie));
	}


	public function setReturningUserCookie($cookie)
	{
		$this->returningUserCookie = $cookie;
	}


	public function setPermanentLoginCookie($cookie): void
	{
		$this->permanentLoginCookie = $cookie;
	}


	/**
	 * Set permanent login interval.
	 *
	 * @param string $interval
	 */
	public function setPermanentLoginInterval($interval)
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
	 * @param integer $type
	 * @return string Concatenation of selector, separator, token
	 * @throws \Exception
	 */
	private function insertToken(User $user, int $type)
	{
		$selector = \Nette\Utils\Random::generate(32, '0-9a-zA-Z');
		$token = \Nette\Utils\Random::generate(64, '0-9a-zA-Z');

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
		} catch (\PDOException $e) {
			if ($e->getCode() == '23000') {
				if ($e->errorInfo[1] == \Nette\Database\Drivers\MySqlDriver::ERROR_DUPLICATE_ENTRY) {
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
	 * @throws \Exception
	 */
	public function storePermanentLogin(User $user)
	{
		$value = $this->insertToken($user, self::TOKEN_PERMANENT_LOGIN);
		$this->httpResponse->setCookie($this->permanentLoginCookie, $value, $this->permanentLoginInterval, $this->authCookiesPath, null, null, null, 'Strict');
	}


	/**
	 * Delete all permanent login tokens and delete the cookie in the browser.
	 *
	 * @param User $user
	 */
	public function clearPermanentLogin(User $user)
	{
		$this->database->query('DELETE FROM auth_tokens WHERE key_user = ? AND type = ?', $user->getId(), self::TOKEN_PERMANENT_LOGIN);
		$this->httpResponse->deleteCookie($this->permanentLoginCookie, $this->authCookiesPath);
	}


	/**
	 * Regenerate permanent login token.
	 *
	 * @param User $user
	 * @throws \Exception
	 */
	public function regeneratePermanentLogin(User $user)
	{
		$this->database->beginTransaction();
		$this->database->query('DELETE FROM auth_tokens WHERE key_user = ? AND type = ?', $user->getId(), self::TOKEN_PERMANENT_LOGIN);
		$this->storePermanentLogin($user);
		$this->database->commit();
	}


	/**
	 * Verify and return permanent token, if present, and valid.
	 *
	 * @return Row|null
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
	 * @return Row|null
	 */
	public function verifyReturningUser(string $value): ?Row
	{
		return $this->verifyToken($value, DateTime::fromParts(2000, 1, 1), self::TOKEN_RETURNING_USER);
	}


	/**
	 * Regenerate returning user token.
	 *
	 * @param User $user
	 * @throws \Exception
	 */
	public function regenerateReturningUser(User $user)
	{
		$this->database->beginTransaction();
		$this->database->query('DELETE FROM auth_tokens WHERE key_user = ? AND type = ?', $user->getId(), self::TOKEN_RETURNING_USER);
		$this->setReturningUser($this->insertToken($user, self::TOKEN_RETURNING_USER));
		$this->database->commit();
	}


	/**
	 * Verify and return any token, if present, and valid.
	 *
	 * @param string $value
	 * @param \DateTimeInterface $validity
	 * @param int $type
	 * @return Row|null
	 */
	private function verifyToken(string $value, \DateTimeInterface $validity, int $type): ?Row
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
				$type
			);
			if ($storedToken && hash_equals($storedToken->token, $this->hashToken($values[1]))) {
				$result = $storedToken;
			}
		}
		return $result;
	}

}
