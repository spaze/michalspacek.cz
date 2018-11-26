<?php
namespace MichalSpacekCz\User;

use Nette\Security\User;

/**
 * Manager model.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
class Manager implements \Nette\Security\IAuthenticator
{

	private const AUTH_COOKIES_PATH = '/';

	private const AUTH_PERMANENT_COOKIE = 'permanent';

	private const AUTH_SELECTOR_TOKEN_SEPARATOR = ':';

	private const TOKEN_PERMANENT_LOGIN = 1;

	private const TOKEN_RETURNING_USER = 2;

	/** @var \Nette\Database\Context */
	protected $database;

	/** @var \Nette\Http\IRequest */
	protected $httpRequest;

	/** @var \Nette\Http\IResponse */
	protected $httpResponse;

	/** @var \MichalSpacekCz\Encryption\Password */
	protected $passwordEncryption;

	/** @var string */
	private $returningUserCookie;

	/** @var string */
	private $permanentLoginInterval;


	public function __construct(
		\Nette\Database\Context $context,
		\Nette\Http\IRequest $httpRequest,
		\Nette\Http\IResponse $httpResponse,
		\MichalSpacekCz\Encryption\Password $passwordEncryption
	)
	{
		$this->database = $context;
		$this->httpRequest = $httpRequest;
		$this->httpResponse = $httpResponse;
		$this->passwordEncryption = $passwordEncryption;
	}


	/**
	 * Performs an authentication.
	 *
	 * @return \Nette\Security\Identity
	 *
	 * @throws \Nette\Security\AuthenticationException
	 */
	public function authenticate(array $credentials)
	{
		list($username, $password) = $credentials;
		$user = $this->verifyPassword($username, $password);
		return $this->getIdentity($user->userId, $user->username);
	}


	/**
	 * Get identity object.
	 *
	 * @param integer $id User id
	 * @param string $username Username
	 * @return \Nette\Security\Identity
	 */
	public function getIdentity($id, $username)
	{
		return new \Nette\Security\Identity($id, array(), array('username' => $username));
	}


	private function verifyPassword($username, $password)
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
		if (!$this->verifyHash($password, $this->passwordEncryption->decrypt($user->password))) {
			throw new \Nette\Security\AuthenticationException('The password is incorrect.', self::INVALID_CREDENTIAL);
		}
		return $user;
	}


	private function calculateHash($password)
	{
		return password_hash($password, PASSWORD_DEFAULT);
	}


	private function verifyHash($password, $hash)
	{
		return password_verify($password, $hash);
	}


	public function changePassword(User $user, $password, $newPassword)
	{
		$this->verifyPassword($user->getIdentity()->username, $password);
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
		$this->httpResponse->setCookie($this->returningUserCookie, $value, \Nette\Http\Response::PERMANENT, self::AUTH_COOKIES_PATH);
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
					'created' => new \DateTime(),
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
	 */
	public function storePermanentLogin(User $user)
	{
		$value = $this->insertToken($user, self::TOKEN_PERMANENT_LOGIN);
		$this->httpResponse->setCookie(self::AUTH_PERMANENT_COOKIE,  $value, $this->permanentLoginInterval, self::AUTH_COOKIES_PATH);
	}


	/**
	 * Delete all permanent login tokens and delete the cookie in the browser.
	 *
	 * @param User $user
	 */
	public function clearPermanentLogin(User $user) {
		$this->database->query('DELETE FROM auth_tokens WHERE key_user = ? AND type = ?', $user->getId(), self::TOKEN_PERMANENT_LOGIN);
		$this->httpResponse->deleteCookie(self::AUTH_PERMANENT_COOKIE,  self::AUTH_COOKIES_PATH);
	}


	/**
	 * Verify and return permanent token, if present, and valid.
	 *
	 * @return \Nette\Database\Row|null
	 */
	public function verifyPermanentLogin(): ?\Nette\Database\Row
	{
		$cookie = $this->httpRequest->getCookie(self::AUTH_PERMANENT_COOKIE, '');
		return $this->verifyToken($cookie, new \DateTime("-{$this->permanentLoginInterval}"), self::TOKEN_PERMANENT_LOGIN);
	}


	/**
	 * Verify returning user, if present, and valid.
	 *
	 * @param string $value
	 * @return \Nette\Database\Row|null
	 */
	public function verifyReturningUser(string $value): ?\Nette\Database\Row
	{
		return $this->verifyToken($value, new \DateTime('2000-01-01 UTC'), self::TOKEN_RETURNING_USER);
	}


	/**
	 * Verify and return any token, if present, and valid.
	 *
	 * @param string $value
	 * @param \DateTimeInterface $validity
	 * @param int $type
	 * @return \Nette\Database\Row|null
	 */
	private function verifyToken(string $value, \DateTimeInterface $validity, int $type): ?\Nette\Database\Row
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
