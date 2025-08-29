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
			cr.certificate_name AS certificateName,
			cr.certificate_name_ext AS certificateNameExt,
			cr.cn,
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
				GROUP BY cr.certificate_name, cr.certificate_name_ext
			)
			ORDER BY cr.certificate_name, cr.certificate_name_ext';
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
	 * @throws SomeCertificatesLoggedToFileException
	 */
	public function log(Certificate $cert): void
	{
		$dbException = null;
		try {
			$this->database->beginTransaction();
			$this->database->query('INSERT INTO certificates', [
				'key_certificate_request' => $this->logRequest($cert),
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
				$cert->getCertificateName(),
				$cert->getCertificateNameExtension() ?? '',
				$cert->getNotBefore()->format(DateTimeFormat::RFC3339_MICROSECONDS),
				$cert->getNotAfter()->format(DateTimeFormat::RFC3339_MICROSECONDS),
			), 'cert');
			$dbException = $e;
		}
		if ($dbException !== null) {
			throw new SomeCertificatesLoggedToFileException(previous: $dbException);
		}
	}


	private function logRequest(Certificate $certificate): int
	{
		$now = new DateTimeImmutable();
		$this->database->query('INSERT INTO certificate_requests', [
			'certificate_name' => $certificate->getCertificateName(),
			'certificate_name_ext' => $certificate->getCertificateNameExtension(),
			'cn' => $certificate->getCommonName(),
			'time' => $now,
			'time_timezone' => $now->getTimezone()->getName(),
			'success' => true,
		]);
		return (int)$this->database->getInsertId();
	}

}
