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
		private int $expiringThreshold,
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
	 * @param array{commonName:string, commonNameExt:string|null, notBefore:string, notBeforeTz:string, notAfter:string, notAfterTz:string, expiringThreshold:int, serialNumber:string|null, now:string, nowTz:string} $details
	 * @return Certificate
	 * @throws CannotParseDateTimeException
	 * @throws CertificateException
	 */
	public function fromArray(array $details): Certificate
	{
		return new Certificate(
			$details['commonName'],
			$details['commonNameExt'],
			$this->createDateTimeImmutable($details['notBefore'], $details['notBeforeTz']),
			$this->createDateTimeImmutable($details['notAfter'], $details['notAfterTz']),
			$details['expiringThreshold'],
			$details['serialNumber'],
			$this->createDateTimeImmutable($details['now'], $details['nowTz']),
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
