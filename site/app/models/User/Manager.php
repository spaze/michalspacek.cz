<?php
namespace MichalSpacekCz\User;

use \Nette\Security\User;

/**
 * Manager model.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
class Manager implements \Nette\Security\IAuthenticator
{

	const AUTH_COOKIES_PATH = '/';

	const AUTH_PERMANENT_COOKIE = 'permanent';

	const AUTH_SELECTOR_TOKEN_SEPARATOR = ':';

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
	private $returningUserValue;

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


	public function setReturningUser()
	{
		$this->httpResponse->setCookie($this->returningUserCookie, $this->returningUserValue, \Nette\Http\Response::PERMANENT, self::AUTH_COOKIES_PATH);
	}


	public function isReturningUser()
	{
		return ($this->httpRequest->getCookie($this->returningUserCookie) === $this->returningUserValue);
	}


	public function setReturningUserCookie($cookie)
	{
		$this->returningUserCookie = $cookie;
	}


	public function setReturningUserValue($value)
	{
		$this->returningUserValue = $value;
	}


	public function isReturningUserValue($value)
	{
		return ($this->returningUserValue === $value);
	}


	/**
	 * Set permanent login interval.
	 *
	 * @param string
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
	 * @param \Nette\Security\User $user
	 * @return string Concatenation of selector, separator, token
	 */
	private function insertToken(User $user)
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
				)
			);
		} catch (\PDOException $e) {
			if ($e->getCode() == '23000') {
				if ($e->errorInfo[1] == '1062') {  // Integrity constraint violation: 1062 Duplicate entry '...' for key 'selector'
					// regenerate the access code and try harder this time
					return $this->insertToken($user);
				}
			}
			throw $e;
		}
		return $selector . self::AUTH_SELECTOR_TOKEN_SEPARATOR . $token;
	}


	/**
	 * Store permanent login token in database and send a cookie to the browser.
	 *
	 * @param \Nette\Security\User $user
	 */
	public function storePermanentLogin(User $user)
	{
		$value = $this->insertToken($user);
		$this->httpResponse->setCookie(self::AUTH_PERMANENT_COOKIE,  $value, $this->permanentLoginInterval, self::AUTH_COOKIES_PATH);
	}


	/**
	 * Delete all permanent login tokens and delete the cookie in the browser.
	 *
	 * @param \Nette\Security\User $user
	 */
	public function clearPermanentLogin(User $user) {
		$this->database->query('DELETE FROM auth_tokens WHERE key_user = ?', $user->getId());
		$this->httpResponse->deleteCookie(self::AUTH_PERMANENT_COOKIE,  self::AUTH_COOKIES_PATH);
	}


	/**
	 * Verify and return permanent token, if present, and valid.
	 *
	 * @return false|\Nette\Database\Row
	 */
	public function verifyPermanentLogin()
	{
		$result = false;
		$value = $this->httpRequest->getCookie(self::AUTH_PERMANENT_COOKIE);
		if ($value !== null) {
			list($selector, $token) = explode(self::AUTH_SELECTOR_TOKEN_SEPARATOR, $value);
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
					AND at.created > ?',
				$selector,
				new \DateTime('-' . $this->permanentLoginInterval)
			);
			if ($storedToken !== false && hash_equals($storedToken->token, $this->hashToken($token))) {
				$result = $storedToken;
			}
		}
		return $result;
	}

}
