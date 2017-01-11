<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Api;

class Certificates
{

	/** @var array */
	private $users;


	/**
	 * Set users authentication info.
	 * @param array $users
	 */
	public function setUsers(array $users): void
	{
		$this->users = $users;
	}


	/**
	 * @param string $user
	 * @param string $key
	 */
	public function authenticate(string $user, string $key): void
	{
		if (!isset($this->users[$user])) {
			throw new \Nette\Security\AuthenticationException('Unknown user', \MichalSpacekCz\User\Manager::IDENTITY_NOT_FOUND);
		}

		if (!password_verify($key, $this->users[$user])) {
			throw new \Nette\Security\AuthenticationException('Invalid key', \MichalSpacekCz\User\Manager::INVALID_CREDENTIAL);
		}
	}


	/**
	 * Log certificates.
	 *
	 * @param array $certs
	 * @param array $failures
	 * @return array with counts
	 */
	public function log(array $certs, array $failures): array
	{
		foreach ($certs as $cn => $dates) {
			$start = date(\DateTime::ATOM, (int)$dates['start']);
			$expiry = date(\DateTime::ATOM, (int)$dates['expiry']);
			\Tracy\Debugger::log("OK $cn from $start to $expiry", 'cert');
		}
		foreach ($failures as $cn) {
			\Tracy\Debugger::log("FAIL $cn", 'cert');
		}
		return [
			'certificates' => count($certs),
			'failures' => count($failures),
		];
	}

}
