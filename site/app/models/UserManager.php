<?php
namespace MichalSpacekCz;

/**
 * UserManager model.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
class UserManager implements \Nette\Security\IAuthenticator
{

	const KNOCK_KNOCK = 'knockKnock';

	const CIPHER_NAME = MCRYPT_RIJNDAEL_128;

	const CIPHER_MODE = MCRYPT_MODE_CBC;

	const RETURNING_USER_COOKIE = 'beenhere';

	const RETURNING_USER_VALUE = 'donethat';

	const RETURNING_USER_PATH = '/';

	/** @var \Nette\Database\Connection */
	protected $database;

	/** @var \Nette\Http\IRequest */
	protected $httpRequest;

	protected $key;


	public function __construct(\Nette\Database\Connection $connection, \Nette\Http\IRequest $httpRequest)
	{
		$this->database = $connection;
		$this->httpRequest = $httpRequest;
	}


	public function setKey($key)
	{
		if (strlen($key) != 64 || !ctype_xdigit($key)) {
			throw new \InvalidArgumentException('Key must be 64 characters long and only consist of hexadecimal characters');
		}
		$this->key = $key;
	}


	public function verifySignInAuthorization($knockKnock)
	{
		if ($knockKnock != self::KNOCK_KNOCK && !$this->isReturningUser()) {
			throw new \Nette\Application\BadRequestException("Knock, knock. Who's there? GTFO!", \Nette\Http\Response::S404_NOT_FOUND);
		}
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
		$this->setReturningUser();
		return new \Nette\Security\Identity($user->userId, array(), array('username' => $user->username));
	}


	public function verifyPassword($username, $password)
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
		if (!$this->verifyHash($password, $this->decryptPassword($user->password))) {
			throw new \Nette\Security\AuthenticationException('The password is incorrect.', self::INVALID_CREDENTIAL);
		}
		return $user;
	}


	private function calculateHash($password, $salt = null)
	{
		return password_hash($password, PASSWORD_DEFAULT);
	}

	private function verifyHash($password, $hash)
	{
		return password_verify($password, $hash);
	}

	private function getIvSize()
	{
		return $ivSize = mcrypt_get_iv_size(self::CIPHER_NAME, self::CIPHER_MODE);
	}


	protected function encryptPassword($password)
	{
		$key = pack('H*', $this->key);
		$iv = mcrypt_create_iv($this->getIvSize(), MCRYPT_RAND);
		$encrypted = mcrypt_encrypt(self::CIPHER_NAME, $key, $password, self::CIPHER_MODE, $iv);
		return base64_encode($iv . $encrypted);
	}


	protected function decryptPassword($password)
	{
		$encrypted = base64_decode($password);
		$key = pack('H*', $this->key);
		$iv = substr($encrypted, 0, $this->getIvSize());
		$encrypted = substr($encrypted, $this->getIvSize());
		$decrypted = mcrypt_decrypt(self::CIPHER_NAME, $key, $encrypted, self::CIPHER_MODE, $iv);
		return rtrim($decrypted, "\0");
	}


	public function changePassword($username, $password, $newPassword)
	{
		$user = $this->verifyPassword($username, $password);
		$this->database->query('UPDATE users SET password = ? WHERE id_user = ?', $this->encryptPassword($this->calculateHash($newPassword)), $user->userId);
	}


	public function isForbidden()
	{
		$forbidden = $this->database->fetchColumn(
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


	private function setReturningUser()
	{
		$this->httpResponse->setCookie(self::RETURNING_USER_COOKIE, self::RETURNING_USER_VALUE, \Nette\Http\Response::PERMANENT, self::RETURNING_USER_PATH);
	}


	private function isReturningUser()
	{
		return ($this->httpRequest->getCookie(self::RETURNING_USER_COOKIE) != self::RETURNING_USER_VALUE);
	}


}
