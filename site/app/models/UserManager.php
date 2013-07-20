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
		if ($user->password !== $this->calculateHash($password, $user->password)) {
			throw new \Nette\Security\AuthenticationException('The password is incorrect.', self::INVALID_CREDENTIAL);
		}
		return $user;
	}


	public function calculateHash($password, $salt = null)
	{
		return crypt($password, $salt ?: '$2y$07$' . \Nette\Utils\Strings::random(22));
	}


	public function changePassword($username, $password, $newPassword)
	{
		$user = $this->verifyPassword($username, $password);
		$this->database->query('UPDATE users SET password = ? WHERE id_user = ?', $this->calculateHash($newPassword), $user->userId);
	}


}