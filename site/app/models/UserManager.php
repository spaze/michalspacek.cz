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
		$row = $this->database->fetch('SELECT id_user AS idUser, password FROM users WHERE username = ?', $username);

		if (!$row) {
			throw new \Nette\Security\AuthenticationException('The username is incorrect.', self::IDENTITY_NOT_FOUND);
		}

		if ($row->password !== $this->calculateHash($password, $row->password)) {
			throw new \Nette\Security\AuthenticationException('The password is incorrect.', self::INVALID_CREDENTIAL);
		}

		return new \Nette\Security\Identity($row->idUser);
	}


	public function calculateHash($password, $salt = null)
	{
		return crypt($password, $salt ?: '$2y$07$' . \Nette\Utils\Strings::random(22));
	}


}