<?php
namespace MichalSpacekCz;

/**
 * UserManager model.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
class UserManager extends BaseModel implements \Nette\Security\IAuthenticator
{

	const KNOCK_KNOCK = 'knockKnock';

	protected $key;


	public function setKey($key)
	{
		$this->key = $key;
	}


	public function verifySignInAuthorization($knockKnock)
	{
		if ($knockKnock != self::KNOCK_KNOCK) {
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
		return crypt($password, $salt ?: '$2y$07$' . \Nette\Utils\Strings::random(22));
	}

	private function verifyHash($password, $hash)
	{
		return ($hash === $this->calculateHash($password, $hash));
	}

	private function getIvSize()
	{
		return $ivSize = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CBC);
	}


	protected function encryptPassword($password)
	{
		$key = pack('H*', $this->key);
		$iv = mcrypt_create_iv($this->getIvSize(), MCRYPT_RAND);
		$encrypted = mcrypt_encrypt(MCRYPT_RIJNDAEL_128, $key, $password, MCRYPT_MODE_CBC, $iv);
		return base64_encode($iv . $encrypted);
	}


	protected function decryptPassword($password)
	{
		$encrypted = base64_decode($password);
		$key = pack('H*', $this->key);
		$iv = substr($encrypted, 0, $this->getIvSize());
		$encrypted = substr($encrypted, $this->getIvSize());
		$decrypted = mcrypt_decrypt(MCRYPT_RIJNDAEL_128, $key, $encrypted, MCRYPT_MODE_CBC, $iv);
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


}