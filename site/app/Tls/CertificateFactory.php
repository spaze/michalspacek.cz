<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Tls;

use DateTimeImmutable;
use DateTimeZone;
use MichalSpacekCz\DateTime\DateTime;
use MichalSpacekCz\DateTime\DateTimeParser;
use MichalSpacekCz\DateTime\Exceptions\CannotParseDateTimeException;
use MichalSpacekCz\Tls\Exceptions\CertificateException;
use MichalSpacekCz\Tls\Exceptions\OpenSslException;
use Nette\Database\Row;
use OpenSSLCertificate;

class CertificateFactory
{

	public function __construct(
		private readonly int $expiringThreshold,
	) {
	}


	/**
	 * @throws CertificateException
	 */
	public function fromDatabaseRow(Row $row): Certificate
	{
		return new Certificate(
			$row->cn,
			$row->ext,
			DateTimeImmutable::createFromInterface($row->notBefore),
			DateTimeImmutable::createFromInterface($row->notAfter),
			$this->expiringThreshold,
			null,
		);
	}


	/**
	 * @throws OpenSslException
	 * @throws CannotParseDateTimeException
	 * @throws CertificateException
	 */
	public function fromObject(OpenSSLCertificate $certificate): Certificate
	{
		$details = OpenSsl::x509parse($certificate);
		return new Certificate(
			$details['subject']['commonName'],
			null,
			DateTimeParser::createFromFormat('U', (string)$details['validFrom_time_t']),
			DateTimeParser::createFromFormat('U', (string)$details['validTo_time_t']),
			$this->expiringThreshold,
			$details['serialNumberHex'],
		);
	}


	/**
	 * @throws CannotParseDateTimeException
	 * @throws CertificateException
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
	 */
	private function createDateTimeImmutable(string $time, string $timeZone): DateTimeImmutable
	{
		return DateTimeParser::createFromFormat(DateTime::DATE_RFC3339_MICROSECONDS, $time)->setTimezone(new DateTimeZone($timeZone));
	}

}
