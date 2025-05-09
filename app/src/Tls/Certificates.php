<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Tls;

use DateTimeImmutable;
use MichalSpacekCz\Database\TypedDatabase;
use MichalSpacekCz\DateTime\DateTimeFormat;
use MichalSpacekCz\DateTime\Exceptions\DateTimeException;
use MichalSpacekCz\Tls\Exceptions\CertificateException;
use MichalSpacekCz\Tls\Exceptions\SomeCertificatesLoggedToFileException;
use Nette\Database\DriverException;
use Nette\Database\Explorer;
use Nette\Security\AuthenticationException;
use Nette\Security\Authenticator;
use Tracy\Debugger;

final readonly class Certificates
{

	/**
	 * @param array<string, string> $users
	 */
	public function __construct(
		private Explorer $database,
		private TypedDatabase $typedDatabase,
		private CertificateFactory $certificateFactory,
		private array $users,
		private int $hideExpiredAfter,
	) {
	}


	/**
	 * @param string $user
	 * @param string $key
	 * @throws AuthenticationException
	 */
	public function authenticate(string $user, string $key): void
	{
		if (!isset($this->users[$user])) {
			throw new AuthenticationException('Unknown user', Authenticator::IdentityNotFound);
		}

		if (!hash_equals($this->users[$user], hash('sha512', $key))) {
			throw new AuthenticationException('Invalid key', Authenticator::InvalidCredential);
		}
	}


	/**
	 * Get newest certificates.
	 *
	 * @return array<int, Certificate>
	 * @throws CertificateException
	 * @throws DateTimeException
	 */
	public function getNewest(): array
	{
		$query = 'SELECT
			cr.cn,
			cr.ext,
			c.not_before AS notBefore,
			c.not_before_timezone AS notBeforeTimezone,
			c.not_after AS notAfter,
			c.not_after_timezone AS notAfterTimezone
			FROM certificates c
				JOIN certificate_requests cr ON c.key_certificate_request = cr.id_certificate_request
			WHERE c.id_certificate IN (
				SELECT MAX(c.id_certificate)
				FROM certificates c JOIN certificate_requests cr ON c.key_certificate_request = cr.id_certificate_request
				WHERE NOT c.hidden
				GROUP BY cr.cn, cr.ext
			)
			ORDER BY cr.cn, cr.ext';
		$certificates = [];
		foreach ($this->typedDatabase->fetchAll($query) as $data) {
			$certificate = $this->certificateFactory->fromDatabaseRow($data);
			if ($certificate->isExpired() && $certificate->getExpiryDays() > $this->hideExpiredAfter) {
				continue;
			}
			$certificates[] = $certificate;
		}
		return $certificates;
	}


	/**
	 * @param list<Certificate> $certs
	 * @param list<CertificateAttempt> $failures
	 * @return array{certificates:int, failures:int} with counts
	 * @throws SomeCertificatesLoggedToFileException
	 */
	public function log(array $certs, array $failures): array
	{
		$dbException = null;
		foreach ($certs as $cert) {
			try {
				$this->database->beginTransaction();
				$this->database->query('INSERT INTO certificates', [
					'key_certificate_request' => $this->logRequest($cert, true),
					'not_before' => $cert->getNotBefore(),
					'not_before_timezone' => $cert->getNotBefore()->getTimezone()->getName(),
					'not_after' => $cert->getNotAfter(),
					'not_after_timezone' => $cert->getNotAfter()->getTimezone()->getName(),
				]);
				$this->database->commit();
			} catch (DriverException $e) {
				Debugger::log($e);
				Debugger::log(sprintf(
					'OK %s%s from %s to %s',
					$cert->getCommonName(),
					$cert->getCommonNameExt() ?? '',
					$cert->getNotBefore()->format(DateTimeFormat::RFC3339_MICROSECONDS),
					$cert->getNotAfter()->format(DateTimeFormat::RFC3339_MICROSECONDS),
				), 'cert');
				$dbException = $e;
			}
		}
		foreach ($failures as $cert) {
			try {
				$this->logRequest($cert, false);
			} catch (DriverException $e) {
				Debugger::log($e);
				Debugger::log("FAIL {$cert->getCommonName()}{$cert->getCommonNameExt()}", 'cert');
				$dbException = $e;
			}
		}

		if ($dbException !== null) {
			throw new SomeCertificatesLoggedToFileException(previous: $dbException);
		}

		return [
			'certificates' => count($certs),
			'failures' => count($failures),
		];
	}


	private function logRequest(Certificate|CertificateAttempt $certificate, bool $success): int
	{
		$now = new DateTimeImmutable();
		$this->database->query('INSERT INTO certificate_requests', [
			'cn' => $certificate->getCommonName(),
			'ext' => $certificate->getCommonNameExt(),
			'time' => $now,
			'time_timezone' => $now->getTimezone()->getName(),
			'success' => $success,
		]);
		return (int)$this->database->getInsertId();
	}

}
