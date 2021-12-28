<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Tls;

use DateTimeImmutable;
use MichalSpacekCz\Tls\Exceptions\CertificateException;
use MichalSpacekCz\User\Manager;
use Nette\Database\DriverException;
use Nette\Database\Explorer;
use Nette\Security\AuthenticationException;
use Nette\Utils\DateTime as NetteDateTime;
use RuntimeException;
use Tracy\Debugger;

class Certificates
{

	/** @var array<string, string> */
	private array $users;

	private int $hideExpiredAfter;


	public function __construct(
		private Explorer $database,
		private CertificateFactory $certificateFactory,
	) {
	}


	/**
	 * Set users authentication info.
	 *
	 * @param array<string, string> $users
	 */
	public function setUsers(array $users): void
	{
		$this->users = $users;
	}


	/**
	 * Set hide expired after interval.
	 *
	 * @param int $hideExpiredAfter in days
	 */
	public function setHideExpiredAfter(int $hideExpiredAfter): void
	{
		$this->hideExpiredAfter = $hideExpiredAfter;
	}


	/**
	 * @param string $user
	 * @param string $key
	 * @throws AuthenticationException
	 */
	public function authenticate(string $user, string $key): void
	{
		if (!isset($this->users[$user])) {
			throw new AuthenticationException('Unknown user', Manager::IDENTITY_NOT_FOUND);
		}

		if (!hash_equals($this->users[$user], hash('sha512', $key))) {
			throw new AuthenticationException('Invalid key', Manager::INVALID_CREDENTIAL);
		}
	}


	/**
	 * Get newest certificates.
	 *
	 * @return array<int, Certificate>
	 * @throws CertificateException
	 */
	public function getNewest(): array
	{
		$query = 'SELECT
			cr.cn,
			cr.ext,
			MAX(c.not_before) AS notBefore,
			MAX(c.not_after) AS notAfter
			FROM certificates c
				JOIN certificate_requests cr ON c.key_certificate_request = cr.id_certificate_request
			WHERE NOT c.hidden
			GROUP BY cr.cn, cr.ext
			ORDER BY cr.cn, cr.ext';
		$certificates = [];
		foreach ($this->database->fetchAll($query) as $data) {
			$certificate = $this->certificateFactory->fromDatabaseRow($data);
			if ($certificate->isExpired() && $certificate->getExpiryDays() > $this->hideExpiredAfter) {
				continue;
			}
			$certificates[] = $certificate;
		}
		return $certificates;
	}


	/**
	 * Log certificates.
	 *
	 * @param array<string, array<string, string>> $certs
	 * @param array<string, array<string, string>> $failures
	 * @return array{certificates:int, failures:int} with counts
	 */
	public function log(array $certs, array $failures): array
	{
		$databaseLoggedAll = true;
		foreach ($certs as $cnext => $cert) {
			$start = NetteDateTime::from($cert['start']);
			$expiry = NetteDateTime::from($cert['expiry']);
			try {
				$this->database->beginTransaction();
				$this->database->query('INSERT INTO certificates', array(
					'key_certificate_request' => $this->logRequest($cert['cn'], $cert['ext'], true),
					'not_before' => $start,
					'not_after' => $expiry,
				));
				$this->database->commit();
			} catch (DriverException $e) {
				Debugger::log($e);
				Debugger::log("OK $cnext from $start to $expiry", 'cert');
				$databaseLoggedAll = false;
			}
		}
		foreach ($failures as $cnext => $cert) {
			try {
				$this->logRequest($cert['cn'], $cert['ext'], false);
			} catch (DriverException $e) {
				Debugger::log($e);
				Debugger::log("FAIL $cnext", 'cert');
				$databaseLoggedAll = false;
			}
		}

		if (!$databaseLoggedAll) {
			throw new RuntimeException('Error logging to database, some certificates logged to file instead');
		}

		return [
			'certificates' => count($certs),
			'failures' => count($failures),
		];
	}


	/**
	 * @param string $cn
	 * @param string $ext
	 * @param bool $success
	 * @return int
	 */
	private function logRequest(string $cn, string $ext, bool $success): int
	{
		$this->database->query('INSERT INTO certificate_requests', array(
			'cn' => $cn,
			'ext' => (empty($ext) ? null : $ext),
			'time' => new DateTimeImmutable(),
			'success' => $success,
		));
		return (int)$this->database->getInsertId();
	}

}
