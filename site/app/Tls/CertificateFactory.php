<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Tls;

use DateTimeImmutable;
use MichalSpacekCz\DateTime\DateTime;
use MichalSpacekCz\DateTime\DateTimeFactory;
use MichalSpacekCz\DateTime\DateTimeZoneFactory;
use MichalSpacekCz\DateTime\Exceptions\CannotParseDateTimeException;
use MichalSpacekCz\DateTime\Exceptions\DateTimeException;
use MichalSpacekCz\DateTime\Exceptions\InvalidTimezoneException;
use MichalSpacekCz\Tls\Exceptions\CertificateException;
use MichalSpacekCz\Tls\Exceptions\OpenSslException;
use MichalSpacekCz\Tls\Exceptions\OpenSslX509ParseException;
use Nette\Database\Row;
use OpenSSLCertificate;

readonly class CertificateFactory
{

	public function __construct(
		private DateTimeZoneFactory $dateTimeZoneFactory,
		private DateTimeFactory $dateTimeFactory,
		private int $expiringThreshold,
	) {
	}


	/**
	 * @throws CertificateException
	 * @throws DateTimeException
	 */
	public function fromDatabaseRow(Row $row): Certificate
	{
		return new Certificate(
			$row->cn,
			$row->ext,
			$this->dateTimeFactory->createFrom($row->notBefore, $row->notBeforeTimezone),
			$this->dateTimeFactory->createFrom($row->notAfter, $row->notAfterTimezone),
			$this->expiringThreshold,
			null,
		);
	}


	/**
	 * @throws OpenSslException
	 * @throws CannotParseDateTimeException
	 * @throws CertificateException
	 * @throws OpenSslX509ParseException
	 */
	public function fromObject(OpenSSLCertificate $certificate): Certificate
	{
		$details = OpenSsl::x509parse($certificate);
		return new Certificate(
			$details->getCommonName(),
			null,
			$this->dateTimeFactory->createFromFormat('U', (string)$details->getValidFromTimeT()),
			$this->dateTimeFactory->createFromFormat('U', (string)$details->getValidToTimeT()),
			$this->expiringThreshold,
			$details->getSerialNumberHex(),
		);
	}


	/**
	 * @throws CannotParseDateTimeException
	 * @throws CertificateException
	 * @throws InvalidTimezoneException
	 */
	public function get(
		string $commonName,
		?string $commonNameExt,
		string $notBefore,
		string $notBeforeTz,
		string $notAfter,
		string $notAfterTz,
		int $expiringThreshold,
		?string $serialNumber,
		string $now,
		string $nowTz,
	): Certificate {
		return new Certificate(
			$commonName,
			$commonNameExt,
			$this->createDateTimeImmutable($notBefore, $notBeforeTz),
			$this->createDateTimeImmutable($notAfter, $notAfterTz),
			$expiringThreshold,
			$serialNumber,
			$this->createDateTimeImmutable($now, $nowTz),
		);
	}


	/**
	 * @param string $time
	 * @param string $timeZone
	 * @return DateTimeImmutable
	 * @throws CannotParseDateTimeException
	 * @throws InvalidTimezoneException
	 */
	private function createDateTimeImmutable(string $time, string $timeZone): DateTimeImmutable
	{
		return $this->dateTimeFactory->createFromFormat(DateTime::DATE_RFC3339_MICROSECONDS, $time)->setTimezone($this->dateTimeZoneFactory->get($timeZone));
	}


	/**
	 * @param array<string|int, mixed> $request
	 * @return list<Certificate>
	 * @throws CertificateException
	 */
	public function listFromLogRequest(array $request): array
	{
		$certs = [];
		foreach ($request as $cert) {
			if (
				is_array($cert)
				&& isset($cert['cn'], $cert['ext'], $cert['start'], $cert['expiry'])
				&& is_string($cert['cn'])
				&& is_string($cert['ext'])
				&& is_numeric($cert['start'])
				&& is_numeric($cert['expiry'])
			) {
				$certs[] = new Certificate(
					$cert['cn'],
					$cert['ext'] ?: null,
					new DateTimeImmutable("@{$cert['start']}"),
					new DateTimeImmutable("@{$cert['expiry']}"),
					$this->expiringThreshold,
					null,
				);
			}
		}
		return $certs;
	}

}
