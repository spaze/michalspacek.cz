<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Tls;

use DateTimeImmutable;
use JsonSerializable;
use MichalSpacekCz\DateTime\DateTimeFormat;
use Override;

final class Certificate implements JsonSerializable
{

	private ?int $validityPeriod = null;
	private ?int $expiryDays = null;
	private ?bool $expired = null;
	private ?bool $expiringSoon = null;


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


	public function getValidityPeriod(): int
	{
		if ($this->validityPeriod === null) {
			$validityPeriod = $this->notAfter->diff($this->notBefore)->days;
			assert(is_int($validityPeriod));
			$this->validityPeriod = $validityPeriod;
		}
		return $this->validityPeriod;
	}


	public function getExpiryDays(): int
	{
		if ($this->expiryDays === null) {
			$expiryDays = $this->notAfter->diff($this->now)->days;
			assert(is_int($expiryDays));
			$this->expiryDays = $expiryDays;
		}
		return $this->expiryDays;
	}


	public function isExpired(): bool
	{
		if ($this->expired === null) {
			$this->expired = $this->notAfter < $this->now;
		}
		return $this->expired;
	}


	public function isExpiringSoon(): bool
	{
		if ($this->expiringSoon === null) {
			// Let's Encrypt: we recommend renewing 90-day certificates every 60 days and six day certificates every three days.
			$this->expiringSoon = !$this->isExpired() && $this->getExpiryDays() < ($this->getValidityPeriod() === 6 ? 3 : $this->getValidityPeriod() / 3);
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

}
