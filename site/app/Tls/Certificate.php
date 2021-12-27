<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Tls;

use DateTimeImmutable;
use MichalSpacekCz\Tls\Exceptions\CertificateException;

class Certificate
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
	) {
		$now = new DateTimeImmutable();

		$validDays = $this->notBefore->diff($now)->days;
		if ($validDays === false) {
			throw new CertificateException('Unknown number of valid days');
		}
		$this->validDays = $validDays;
		$expiryDays = $this->notAfter->diff($now)->days;
		if ($expiryDays === false) {
			throw new CertificateException('Unknown number of expiry days');
		}
		$this->expiryDays = $expiryDays;

		$this->expired = $this->notAfter < $now;
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

}
