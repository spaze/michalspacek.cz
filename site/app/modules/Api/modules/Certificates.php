<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Api;

class Certificates
{

	/** @var \Nette\Database\Context */
	protected $database;

	/** @var array */
	private $users;

	/** @var integer */
	private $expiringThreshold;


	/**
	 * @param \Nette\Database\Context $context
	 */
	public function __construct(\Nette\Database\Context $context)
	{
		$this->database = $context;
	}


	/**
	 * Set users authentication info.
	 * @param array $users
	 */
	public function setUsers(array $users): void
	{
		$this->users = $users;
	}


	/**
	 * Set expiring warning threshold.
	 * @param integer $expiringThreshold in days
	 */
	public function setExpiringThreshold(int $expiringThreshold): void
	{
		$this->expiringThreshold = $expiringThreshold;
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
	 * Get newest certificates
	 * @return \Nette\Database\Row[]
	 */
	public function getNewest(): array
	{
		$now = new \DateTime();

		$query = 'SELECT
			cr.cnext AS cnExt,
			MAX(c.not_after) AS notAfter
			FROM certificates c
				JOIN certificate_requests cr ON c.key_certificate_request = cr.id_certificate_request
			GROUP BY cr.cnext
			ORDER BY cr.cnext';
		$certificates = $this->database->fetchAll($query);

		foreach ($certificates as $certificate) {
			$certificate->expired = $certificate->notAfter < $now;
			$certificate->expiryDays = $certificate->notAfter->diff($now)->days;
			$certificate->expiringSoon = !$certificate->expired && $certificate->expiryDays < $this->expiringThreshold;
		}

		return $certificates;
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
		$databaseLoggedAll = true;
		foreach ($certs as $cnext => $dates) {
			$start = \Nette\Utils\DateTime::from($dates['start']);
			$expiry = \Nette\Utils\DateTime::from($dates['expiry']);
			try {
				$this->database->beginTransaction();
				$this->database->query('INSERT INTO certificates', array(
					'key_certificate_request' => $this->logRequest($cnext, true),
					'not_before' => $start,
					'not_after' => $expiry,
				));
				$this->database->commit();
			} catch (\Nette\Database\DriverException $e) {
				\Tracy\Debugger::log($e);
				\Tracy\Debugger::log("OK $cnext from $start to $expiry", 'cert');
				$databaseLoggedAll = false;
			}
		}
		foreach ($failures as $cnext) {
			try {
				$this->logRequest($cnext, false);
			} catch (\Nette\Database\DriverException $e) {
				\Tracy\Debugger::log($e);
				\Tracy\Debugger::log("FAIL $cnext", 'cert');
				$databaseLoggedAll = false;
			}
		}

		if (!$databaseLoggedAll) {
			throw new \RuntimeException('Error logging to database, some certificates logged to file instead');
		}

		return [
			'certificates' => count($certs),
			'failures' => count($failures),
		];
	}


	/**
	 * @param string $cnext
	 * @param boolean $success
	 * @return integer
	 */
	private function logRequest(string $cnext, bool $success): int
	{
		$this->database->query('INSERT INTO certificate_requests', array(
			'cnext' => $cnext,
			'time' => new \DateTime(),
			'success' => $success,
		));
		return (int)$this->database->getInsertId();
	}

}
