<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Tls;

use DateTime;
use DateTimeImmutable;
use MichalSpacekCz\DateTime\DateTimeFactory;
use MichalSpacekCz\DateTime\DateTimeFormat;
use MichalSpacekCz\DateTime\DateTimeZoneFactory;
use MichalSpacekCz\DateTime\Exceptions\CannotParseDateTimeException;
use MichalSpacekCz\DateTime\Exceptions\DateTimeException;
use MichalSpacekCz\DateTime\Exceptions\InvalidTimezoneException;
use MichalSpacekCz\Tls\Exceptions\CertificateException;
use MichalSpacekCz\Tls\Exceptions\OpenSslException;
use MichalSpacekCz\Tls\Exceptions\OpenSslX509ParseException;
use Nette\Database\Row;
use OpenSSLCertificate;

final readonly class CertificateFactory
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
		assert(is_string($row->cn));
		assert($row->ext === null || is_string($row->ext));
		assert($row->notBefore instanceof DateTime);
		assert(is_string($row->notBeforeTimezone));
		assert($row->notAfter instanceof DateTime);
		assert(is_string($row->notAfterTimezone));

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
	public function fromObject(string $certificateName, OpenSSLCertificate $certificate): Certificate
	{
		return $this->fromStringOrObject($certificateName, $certificate);
	}


	/**
	 * @throws OpenSslException
	 * @throws CannotParseDateTimeException
	 * @throws CertificateException
	 * @throws OpenSslX509ParseException
	 */
	public function fromString(string $certificateName, string $certificate): Certificate
	{
		return $this->fromStringOrObject($certificateName, $certificate);
	}


	/**
	 * @throws OpenSslException
	 * @throws CannotParseDateTimeException
	 * @throws CertificateException
	 * @throws OpenSslX509ParseException
	 */
	private function fromStringOrObject(string $certificateName, OpenSSLCertificate|string $certificate): Certificate
	{
		$details = OpenSsl::x509parse($certificate);
		return new Certificate(
			$certificateName,
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
		string $certificateName,
		?string $certificateNameExt,
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
			$certificateName,
			$certificateNameExt,
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
		return $this->dateTimeFactory->createFromFormat(DateTimeFormat::RFC3339_MICROSECONDS, $time)->setTimezone($this->dateTimeZoneFactory->get($timeZone));
	}

}
