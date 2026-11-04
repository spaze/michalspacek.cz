<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Signature;

use DateTimeImmutable;
use SensitiveParameter;
use Spaze\SecurityTxt\Exceptions\SecurityTxtError;
use Spaze\SecurityTxt\Exceptions\SecurityTxtWarning;
use Spaze\SecurityTxt\Signature\Exceptions\SecurityTxtCannotCreateSignatureException;
use Spaze\SecurityTxt\Signature\Exceptions\SecurityTxtCannotCreateSignatureExtensionNotLoadedException;
use Spaze\SecurityTxt\Signature\Exceptions\SecurityTxtCannotVerifySignatureException;
use Spaze\SecurityTxt\Signature\Exceptions\SecurityTxtSigningKeyBadPassphraseException;
use Spaze\SecurityTxt\Signature\Exceptions\SecurityTxtSigningKeyNoPassphraseSetException;
use Spaze\SecurityTxt\Signature\Exceptions\SecurityTxtUnknownSigningKeyException;
use Spaze\SecurityTxt\Signature\Exceptions\SecurityTxtUnusableSigningKeyException;
use Spaze\SecurityTxt\Signature\Providers\SecurityTxtSignatureProvider;
use Spaze\SecurityTxt\Violations\SecurityTxtSignatureCannotVerify;
use Spaze\SecurityTxt\Violations\SecurityTxtSignatureExpired;
use Spaze\SecurityTxt\Violations\SecurityTxtSignatureExtensionNotLoaded;
use Spaze\SecurityTxt\Violations\SecurityTxtSignatureInvalid;
use Spaze\SecurityTxt\Violations\SecurityTxtSignatureKeyExpired;
use Spaze\SecurityTxt\Violations\SecurityTxtSignatureKeyRevoked;
use Spaze\SecurityTxt\Violations\SecurityTxtSignedFileMalformed;
use Spaze\SecurityTxt\Violations\SecurityTxtSpecViolation;

final class SecurityTxtSignature
{

	private const int GPG_ERROR_GPG_AGENT_BAD_PASSPHRASE = 67108875;
	private const int GPG_ERROR_GPG_AGENT_NO_PASSPHRASE = 67109041;
	private const int GPG_ERROR_GPGME_END_OF_FILE = 117456895;

	private const string CLEARSIGN_HEADER = '-----BEGIN PGP SIGNED MESSAGE-----';
	private const string SIGNATURE_ARMOR_HEADER = '-----BEGIN PGP SIGNATURE-----';
	private const string SIGNATURE_ARMOR_TAIL = '-----END PGP SIGNATURE-----';
	private const int PACKET_TAG_SIGNATURE = 2;

	/** RFC 4880 Section 6.3, a longer line the verifier may read only part of */
	private const int ARMOR_LINE_MAX_LENGTH = 76;

	/** The RFC 2045 `token` the format's `hash-alg` and armor header keys are */
	private const string TOKEN = '[!#$%&\'*+.^_`{|}~0-9A-Za-z-]+';

	/** @var array<string, true> */
	private array $addedSignKeys = [];


	public function __construct(private SecurityTxtSignatureProvider $signatureProvider)
	{
	}


	/**
	 * @throws SecurityTxtError
	 * @throws SecurityTxtWarning
	 */
	public function verify(string $contents): SecurityTxtSignatureVerifyResult
	{
		try {
			$signature = $this->signatureProvider->verify($contents);
		} catch (SecurityTxtCannotCreateSignatureExtensionNotLoadedException $e) {
			throw new SecurityTxtWarning(new SecurityTxtSignatureExtensionNotLoaded(), $e);
		} catch (SecurityTxtCannotVerifySignatureException $e) {
			$errorInfo = $e->getErrorInfo();
			$violation = new SecurityTxtSignatureCannotVerify($errorInfo->getMessageAsString(), $errorInfo->getCodeAsString(), $errorInfo->getSourceAsString(), $errorInfo->getLibraryMessageAsString());
			throw new SecurityTxtWarning($violation, $e);
		}

		$summary = $signature->getSummary();
		// A good signature by a revoked key may have been made with a stolen one, a bad one is invalid whoever's key it is
		if (($summary & GNUPG_SIGSUM_KEY_REVOKED) !== 0 && ($summary & GNUPG_SIGSUM_RED) === 0) {
			throw new SecurityTxtError(new SecurityTxtSignatureKeyRevoked($signature->getFingerprint()));
		}
		// GnuPG fails an expired signature, while it gives one the green bit when the keyring trusts the key
		if (($summary & GNUPG_SIGSUM_SIG_EXPIRED) !== 0 && ($summary & GNUPG_SIGSUM_RED) === 0) {
			throw new SecurityTxtError(new SecurityTxtSignatureExpired());
		}
		// Not accepted as GnuPG accepts it, because the summary for a key that has expired and been revoked says only that it has expired
		if (($summary & GNUPG_SIGSUM_KEY_EXPIRED) !== 0 && ($summary & GNUPG_SIGSUM_RED) === 0) {
			throw new SecurityTxtError(new SecurityTxtSignatureKeyExpired($signature->getFingerprint()));
		}
		if (!$this->isSignatureKindaOkay($signature)) {
			throw new SecurityTxtError(new SecurityTxtSignatureInvalid());
		}
		return new SecurityTxtSignatureVerifyResult($signature->getFingerprint(), (new DateTimeImmutable())->setTimestamp($signature->getTimestamp()));
	}


	/**
	 * "Kinda" okay: a structurally valid signature whose key isn't in the keyring (GNUPG_SIGSUM_KEY_MISSING) is accepted
	 * too, not only a fully verified one (GNUPG_SIGSUM_GREEN), and so is a good one by a key the keyring has but doesn't trust, which has no summary bit
	 * and no error at all; a bad signature (GNUPG_SIGSUM_RED) is rejected. The key is usually absent when checking someone else's security.txt, see
	 * "Signature verification" in the README.
	 */
	private function isSignatureKindaOkay(SecurityTxtSignatureVerifySignatureInfo $signature): bool
	{
		$summary = $signature->getSummary();
		if ($summary === 0) {
			return $signature->getStatus() === 0;
		}
		return (($summary & GNUPG_SIGSUM_GREEN) !== 0 || ($summary & GNUPG_SIGSUM_KEY_MISSING) !== 0) && ($summary & GNUPG_SIGSUM_RED) === 0;
	}


	public function isClearsignHeader(string $line): bool
	{
		return $line === self::CLEARSIGN_HEADER;
	}


	public function isSignatureArmorHeader(string $line): bool
	{
		return $line === self::SIGNATURE_ARMOR_HEADER;
	}


	public function isSignatureArmorTail(string $line): bool
	{
		return $line === self::SIGNATURE_ARMOR_TAIL;
	}


	/**
	 * RFC 9116 has a signed file be one cleartext signed message from its first line to its last. Anything less is not verified at all, because the verifier finds the signed
	 * text on its own terms and could verify other lines than the ones the fields were read from.
	 *
	 * @param list<string> $lines The lines as served, each with its line ending
	 * @return array<int<1, max>, SecurityTxtSpecViolation> The line where the file stops being one, empty when it is one
	 */
	public function getFormatViolation(array $lines): array
	{
		$lines = array_map($this->withoutEol(...), $lines);
		$count = count($lines);
		if ($count === 0 || $lines[0] !== self::CLEARSIGN_HEADER) {
			return $this->malformed($lines, 0);
		}
		$index = 1;
		for (; $index < $count && $lines[$index] !== ''; $index++) {
			if (preg_match('/^Hash: ' . self::TOKEN . '(?:,' . self::TOKEN . ')*$/', $lines[$index]) !== 1) {
				return $this->malformed($lines, $index);
			}
		}
		if ($index === 1 || $index >= $count) {
			return $this->malformed($lines, min($index, $count - 1));
		}
		for ($index++; $index < $count && $lines[$index] !== self::SIGNATURE_ARMOR_HEADER; $index++) {
			// The verifier ends the signed text at a line starting with a dash, which the signer escapes, and a CR before anything but an LF is not a line ending to it, nor
			// allowed there by RFC 9116
			if ((str_starts_with($lines[$index], '-') && !str_starts_with($lines[$index], '- ')) || str_ends_with($lines[$index], "\r")) {
				return $this->malformed($lines, $index);
			}
		}
		$armorHeaderIndex = $index;
		for ($index++; $index < $count && $lines[$index] !== ''; $index++) {
			if (preg_match('/^' . self::TOKEN . ': [\x21-\x7E \t]*$/', $lines[$index]) !== 1) {
				return $this->malformed($lines, $index);
			}
		}
		$base64 = '';
		$padded = $checksum = false;
		for ($index++; $index < $count && $lines[$index] !== self::SIGNATURE_ARMOR_TAIL; $index++) {
			$line = $lines[$index];
			if (!$checksum && !$padded && strlen($line) <= self::ARMOR_LINE_MAX_LENGTH && preg_match('~^[A-Za-z0-9+/]+={0,2}$~', $line) === 1) {
				$base64 .= $line;
				$padded = str_ends_with($line, '=');
			} elseif (!$checksum && $base64 !== '' && preg_match('~^=[A-Za-z0-9+/]{4}$~', $line) === 1) {
				$checksum = true;
			} else {
				return $this->malformed($lines, $index);
			}
		}
		if ($index !== $count - 1) {
			return $this->malformed($lines, min($index + 1, $count - 1));
		}
		if (!$this->isSignaturesOnly($base64)) {
			return [$armorHeaderIndex + 1 => new SecurityTxtSignatureInvalid()];
		}
		return [];
	}


	/**
	 * @param list<string> $lines
	 * @return array<int<1, max>, SecurityTxtSpecViolation>
	 */
	private function malformed(array $lines, int $index): array
	{
		$index = max(0, $index);
		return [$index + 1 => new SecurityTxtSignedFileMalformed($lines[$index] ?? '')];
	}


	/**
	 * The verifier reads every packet in the armor and decompresses a compressed one before it finds no signature in it, RFC 9580 Section 4.2 has the packet headers
	 */
	private function isSignaturesOnly(string $base64): bool
	{
		$packets = base64_decode($base64, true);
		if ($packets === false || $packets === '') {
			return false;
		}
		$length = strlen($packets);
		$offset = 0;
		while ($offset < $length) {
			$tag = ord($packets[$offset]);
			if (($tag & 0x80) === 0) {
				return false;
			}
			if (($tag & 0x40) !== 0) {
				if (($tag & 0x3F) !== self::PACKET_TAG_SIGNATURE || $offset + 1 >= $length) {
					return false;
				}
				$first = ord($packets[$offset + 1]);
				if ($first < 192) {
					$headerLength = 2;
					$bodyLength = $first;
				} elseif ($first < 224) {
					$headerLength = 3;
					$second = $this->readNumber($packets, $offset + 2, 1);
					$bodyLength = $second === null ? null : ($first - 192 << 8) + $second + 192;
				} elseif ($first === 255) {
					$headerLength = 6;
					$bodyLength = $this->readNumber($packets, $offset + 2, 4);
				} else {
					// A partial body length, which only data packets may have
					return false;
				}
			} else {
				$lengthType = $tag & 0x03;
				// Length type 3 is an indeterminate length, which only data packets may have
				if ((($tag >> 2) & 0x0F) !== self::PACKET_TAG_SIGNATURE || $lengthType === 3) {
					return false;
				}
				$headerLength = 1 + (1 << $lengthType);
				$bodyLength = $this->readNumber($packets, $offset + 1, 1 << $lengthType);
			}
			// A four octet length can wrap negative in a 32-bit int
			if ($bodyLength === null || $bodyLength < 0 || $offset + $headerLength + $bodyLength > $length) {
				return false;
			}
			$offset += $headerLength + $bodyLength;
		}
		return true;
	}


	private function readNumber(string $bytes, int $offset, int $octets): ?int
	{
		if ($offset + $octets > strlen($bytes)) {
			return null;
		}
		$number = 0;
		for ($i = 0; $i < $octets; $i++) {
			$number = ($number << 8) | ord($bytes[$offset + $i]);
		}
		return $number;
	}


	private function withoutEol(string $line): string
	{
		return str_ends_with($line, "\r\n") ? substr($line, 0, -2) : rtrim($line, "\n");
	}


	/**
	 * Sign the contents with OpenPGP/GnuPG, use only if you know what you're doing.
	 *
	 * Another option is to use the gpg command line utility, as using the sign() method requires
	 * access to your keyring with the signing key, which means your app can also access it,
	 * which means a security problem in your app can leak the signing key. You want to avoid that.
	 *
	 * @param string $keyFingerprint Can be anything that refers to a unique key (user id, key id, fingerprint, ...)
	 * @throws SecurityTxtCannotCreateSignatureException
	 * @throws SecurityTxtCannotCreateSignatureExtensionNotLoadedException
	 * @throws SecurityTxtSigningKeyBadPassphraseException
	 * @throws SecurityTxtSigningKeyNoPassphraseSetException
	 * @throws SecurityTxtUnknownSigningKeyException
	 * @throws SecurityTxtUnusableSigningKeyException
	 */
	public function sign(
		string $text,
		string $keyFingerprint,
		#[SensitiveParameter] ?string $keyPassphrase = null,
	): string {
		$this->addSignKey($keyFingerprint, $keyPassphrase);
		$signed = $this->signatureProvider->sign($text);
		if ($signed === false) {
			throw new SecurityTxtCannotCreateSignatureException($keyFingerprint, $this->signatureProvider->getErrorInfo());
		}
		return $signed;
	}


	/**
	 * @throws SecurityTxtCannotCreateSignatureExtensionNotLoadedException
	 * @throws SecurityTxtSigningKeyBadPassphraseException
	 * @throws SecurityTxtSigningKeyNoPassphraseSetException
	 * @throws SecurityTxtUnknownSigningKeyException
	 * @throws SecurityTxtUnusableSigningKeyException
	 */
	private function addSignKey(string $keyFingerprint, #[SensitiveParameter] ?string $keyPassphrase): void
	{
		if (isset($this->addedSignKeys[$keyFingerprint])) {
			return;
		}
		if (!$this->signatureProvider->addSignKey($keyFingerprint, $keyPassphrase ?? '')) {
			$error = $this->signatureProvider->getErrorInfo();
			if ($error->getCode() === self::GPG_ERROR_GPG_AGENT_NO_PASSPHRASE) {
				throw new SecurityTxtSigningKeyNoPassphraseSetException($keyFingerprint);
			} elseif ($error->getCode() === self::GPG_ERROR_GPG_AGENT_BAD_PASSPHRASE) {
				throw new SecurityTxtSigningKeyBadPassphraseException($keyFingerprint);
			} elseif ($error->getCode() === self::GPG_ERROR_GPGME_END_OF_FILE) {
				throw new SecurityTxtUnknownSigningKeyException($keyFingerprint);
			}
			throw new SecurityTxtUnusableSigningKeyException($keyFingerprint, $error);
		}
		$this->addedSignKeys[$keyFingerprint] = true;
	}

}
