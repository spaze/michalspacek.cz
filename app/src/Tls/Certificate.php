<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Tls;

use DateInterval;
use DateTimeImmutable;
use JsonSerializable;
use MichalSpacekCz\DateTime\DateTimeFormat;
use Override;

final class Certificate implements JsonSerializable
{

	private ?int $validityPeriodDays = null;
	private ?int $validityPeriodHours = null;
	private ?int $expiryDays = null;
	private ?int $expiryHours = null;
	private ?bool $expired = null;
	private ?bool $expiringSoon = null;
	private ?DateTimeImmutable $expiredAfter = null;


	/**
	 * @param list<string>|null $subjectAlternativeNames
	 */
	public function __construct(
		private readonly string $certificateName,
		private readonly ?string $certificateNameExtension,
		private readonly ?string $commonName,
		private readonly ?array $subjectAlternativeNames,
		private readonly DateTimeImmutable $notBefore,
		private readonly DateTimeImmutable $notAfter,
		private readonly ?string $serialNumber,
		private readonly DateTimeImmutable $now,
	) {
	}


	public function getCertificateName(): string
	{
		return $this->certificateName;
	}


	public function getCertificateNameExtension(): ?string
	{
		return $this->certificateNameExtension;
	}


	public function getCommonName(): ?string
	{
		return $this->commonName;
	}


	/**
	 * @return list<string>|null
	 */
	public function getSubjectAlternativeNames(): ?array
	{
		return $this->subjectAlternativeNames;
	}


	public function getNotBefore(): DateTimeImmutable
	{
		return $this->notBefore;
	}


	public function getNotAfter(): DateTimeImmutable
	{
		return $this->notAfter;
	}


	public function getValidityPeriodDays(): int
	{
		if ($this->validityPeriodDays === null) {
			$validityPeriodDays = $this->getExpiredAfter()->diff($this->notBefore)->days;
			assert(is_int($validityPeriodDays));
			$this->validityPeriodDays = $validityPeriodDays;
		}
		return $this->validityPeriodDays;
	}


	public function getValidityPeriodHours(): int
	{
		if ($this->validityPeriodHours === null) {
			$this->validityPeriodHours = (int)(($this->getExpiredAfter()->getTimestamp() - $this->notBefore->getTimestamp()) / 3600);
		}
		return $this->validityPeriodHours;
	}


	public function getExpiryDays(): int
	{
		if ($this->expiryDays === null) {
			$expiryDays = $this->getExpiredAfter()->diff($this->now)->days;
			assert(is_int($expiryDays));
			$this->expiryDays = $expiryDays;
		}
		return $this->expiryDays;
	}


	/**
	 * Return the number of hours either to expiration, or after expiration.
	 */
	public function getExpiryHours(): int
	{
		if ($this->expiryHours === null) {
			$seconds = abs($this->getExpiredAfter()->getTimestamp() - $this->now->getTimestamp());
			$this->expiryHours = (int)floor($seconds / 3600);
		}
		return $this->expiryHours;
	}


	public function isExpired(): bool
	{
		if ($this->expired === null) {
			$this->expired = $this->getExpiredAfter() <= $this->now;
		}
		return $this->expired;
	}


	/**
	 * Is the certificate expiring soon?
	 *
	 * Let's Encrypt recommends renewing 90-day certificates every 60 days and six day certificates every three days,
	 * and because certbot runs only twice a day, we'll give it a few more buffer hours before the certificate will be flagged as expiring.
	 */
	public function isExpiringSoon(): bool
	{
		if ($this->expiringSoon === null) {
			$denominator = $this->getValidityPeriodDays() <= 6 ? 2 : 3;
			$this->expiringSoon = !$this->isExpired() && $this->getExpiryHours() <= (int)($this->getValidityPeriodHours() / $denominator) - 18; // The buffer hours
		}
		return $this->expiringSoon;
	}


	public function hasWarning(): bool
	{
		return $this->isExpired() || $this->isExpiringSoon();
	}


	public function getSerialNumber(): ?string
	{
		return $this->serialNumber;
	}


	/**
	 * @return array{certificateName:string, certificateNameExt:string|null, cn:string|null, san:list<string>|null, notBefore:string, notBeforeTz:string, notAfter:string, notAfterTz:string, serialNumber:string|null, now:string, nowTz:string}
	 */
	#[Override]
	public function jsonSerialize(): array
	{
		return [
			'certificateName' => $this->certificateName,
			'certificateNameExt' => $this->certificateNameExtension,
			'cn' => $this->commonName,
			'san' => $this->getSubjectAlternativeNames(),
			'notBefore' => $this->notBefore->format(DateTimeFormat::RFC3339_MICROSECONDS),
			'notBeforeTz' => $this->notBefore->getTimezone()->getName(),
			'notAfter' => $this->notAfter->format(DateTimeFormat::RFC3339_MICROSECONDS),
			'notAfterTz' => $this->notAfter->getTimezone()->getName(),
			'serialNumber' => $this->serialNumber,
			'now' => $this->now->format(DateTimeFormat::RFC3339_MICROSECONDS),
			'nowTz' => $this->now->getTimezone()->getName(),
		];
	}


	/**
	 * Returns when the certificate becomes expired.
	 *
	 * The certificate is still valid at the notAfter timestamp and becomes expired only the next second after.
	 */
	private function getExpiredAfter(): DateTimeImmutable
	{
		if ($this->expiredAfter === null) {
			$this->expiredAfter = $this->notAfter->add(new DateInterval('PT1S'));
		}
		return $this->expiredAfter;
	}

}
