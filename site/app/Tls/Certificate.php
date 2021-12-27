<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Tls;

use DateTimeImmutable;
use JsonSerializable;
use MichalSpacekCz\DateTime\DateTime;
use MichalSpacekCz\Tls\Exceptions\CertificateException;

class Certificate implements JsonSerializable
{

	private int $validDays;
	private int $expiryDays;
	private bool $expired;
	private bool $expiringSoon;


	/**
	 * @throws CertificateException
	 */
	public function __construct(
		private string $commonName,
		private ?string $commonNameExt,
		private DateTimeImmutable $notBefore,
		private DateTimeImmutable $notAfter,
		private int $expiringThreshold,
		private ?string $serialNumber,
		private DateTimeImmutable $now = new DateTimeImmutable(),
	) {
		$validDays = $this->notBefore->diff($this->now)->days;
		if ($validDays === false) {
			throw new CertificateException('Unknown number of valid days');
		}
		$this->validDays = $validDays;
		$expiryDays = $this->notAfter->diff($this->now)->days;
		if ($expiryDays === false) {
			throw new CertificateException('Unknown number of expiry days');
		}
		$this->expiryDays = $expiryDays;

		$this->expired = $this->notAfter < $this->now;
		$this->expiringSoon = !$this->expired && $this->expiryDays < $this->expiringThreshold;
	}


	public function getCommonName(): string
	{
		return $this->commonName;
	}


	public function getCommonNameExt(): ?string
	{
		return $this->commonNameExt;
	}


	public function getNotBefore(): DateTimeImmutable
	{
		return $this->notBefore;
	}


	public function getNotAfter(): DateTimeImmutable
	{
		return $this->notAfter;
	}


	public function getValidDays(): int
	{
		return $this->validDays;
	}


	public function getExpiryDays(): int
	{
		return $this->expiryDays;
	}


	public function isExpired(): bool
	{
		return $this->expired;
	}


	public function isExpiringSoon(): bool
	{
		return $this->expiringSoon;
	}


	public function getSerialNumber(): ?string
	{
		return $this->serialNumber;
	}


	/**
	 * @return array{commonName:string, commonNameExt:string|null, notBefore:string, notBeforeTz:string, notAfter:string, notAfterTz:string, expiringThreshold:int, serialNumber:string|null, now:string, nowTz:string}
	 */
	public function jsonSerialize(): array
	{
		return [
			'commonName' => $this->commonName,
			'commonNameExt' => $this->commonNameExt,
			'notBefore' => $this->notBefore->format(DateTime::DATE_RFC3339_MICROSECONDS),
			'notBeforeTz' => $this->notBefore->getTimezone()->getName(),
			'notAfter' => $this->notAfter->format(DateTime::DATE_RFC3339_MICROSECONDS),
			'notAfterTz' => $this->notAfter->getTimezone()->getName(),
			'expiringThreshold' => $this->expiringThreshold,
			'serialNumber' => $this->serialNumber,
			'now' => $this->now->format(DateTime::DATE_RFC3339_MICROSECONDS),
			'nowTz' => $this->now->getTimezone()->getName(),
		];
	}

}
