<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Signature;

use DateTimeImmutable;
use JsonSerializable;
use Override;

/**
 * The signer as claimed by a valid OpenPGP cleartext signature.
 *
 * Produced for any signature that isn't bad (not GNUPG_SIGSUM_RED), which includes the common case of the signing
 * key not being in the local keyring: the signature is structurally valid but not verified against a trusted key, so
 * treat the fingerprint and key id as claimed and confirm them out-of-band. See "Signature verification" in the README.
 */
final readonly class SecurityTxtSignatureVerifyResult implements JsonSerializable
{

	private string $keyId;
	private string $shortKeyId;


	public function __construct(
		private string $keyFingerprint,
		private DateTimeImmutable $date,
	) {
		$this->keyId = substr($keyFingerprint, -16);
		$this->shortKeyId = substr($keyFingerprint, -8);
	}


	public function getKeyFingerprint(): string
	{
		return $this->keyFingerprint;
	}


	public function getKeyId(): string
	{
		return $this->keyId;
	}


	public function getShortKeyId(): string
	{
		return $this->shortKeyId;
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
