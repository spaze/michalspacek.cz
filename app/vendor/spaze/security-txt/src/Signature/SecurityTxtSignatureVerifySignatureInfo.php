<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Signature;

final readonly class SecurityTxtSignatureVerifySignatureInfo
{

	/**
	 * @param int|null $status The error the verification ended with, 0 for none
	 */
	public function __construct(
		private int $summary,
		private string $fingerprint,
		private int $timestamp,
		private ?int $status = null,
	) {
	}


	public function getSummary(): int
	{
		return $this->summary;
	}


	public function getFingerprint(): string
	{
		return $this->fingerprint;
	}


	public function getTimestamp(): int
	{
		return $this->timestamp;
	}


	public function getStatus(): ?int
	{
		return $this->status;
	}

}
