<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Signature;

final readonly class SecurityTxtSignatureVerifySignatureInfo
{

	public function __construct(
		private int $summary,
		private string $fingerprint,
		private int $timestamp,
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

}
