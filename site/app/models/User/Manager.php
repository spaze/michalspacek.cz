<?php
namespace MichalSpacekCz\User;

/**
 * Manager model.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
class Manager implements \Nette\Security\IAuthenticator
{

	const RETURNING_USER_PATH = '/';

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
		return new \Nette\Security\Identity($user->userId, array(), array('username' => $user->username));
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


	public function changePassword($username, $password, $newPassword)
	{
		$user = $this->verifyPassword($username, $password);
		$encrypted = $this->passwordEncryption->encrypt($this->calculateHash($newPassword));
		$this->database->query('UPDATE users SET password = ? WHERE id_user = ?', $encrypted, $user->userId);
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
		$this->httpResponse->setCookie($this->returningUserCookie, $this->returningUserValue, \Nette\Http\Response::PERMANENT, self::RETURNING_USER_PATH);
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

}
