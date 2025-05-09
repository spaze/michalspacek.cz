<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Signature;

use DateTimeImmutable;
use JsonSerializable;
use Override;

final readonly class SecurityTxtSignatureVerifyResult implements JsonSerializable
{

	public function __construct(
		private string $keyFingerprint,
		private DateTimeImmutable $date,
	) {
	}


	public function getKeyFingerprint(): string
	{
		return $this->keyFingerprint;
	}


	public function getDate(): DateTimeImmutable
	{
		return $this->date;
	}


	/**
	 * @return array<string, mixed>
	 */
	#[Override]
	public function jsonSerialize(): array
	{
		return [
			'keyFingerprint' => $this->getKeyFingerprint(),
			'dateTime' => $this->getDate()->format(DATE_RFC3339),
		];
	}

}
