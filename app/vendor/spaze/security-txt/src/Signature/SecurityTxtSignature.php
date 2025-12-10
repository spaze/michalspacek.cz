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
use Spaze\SecurityTxt\Violations\SecurityTxtSignatureExtensionNotLoaded;
use Spaze\SecurityTxt\Violations\SecurityTxtSignatureInvalid;

final class SecurityTxtSignature
{

	private const int GPG_ERROR_GPG_AGENT_BAD_PASSPHRASE = 67108875;
	private const int GPG_ERROR_GPG_AGENT_NO_PASSPHRASE = 67109041;
	private const int GPG_ERROR_GPGME_END_OF_FILE = 117456895;

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
			throw new SecurityTxtWarning(new SecurityTxtSignatureCannotVerify($e->getErrorInfo()), $e);
		}

		if (!$this->isSignatureKindaOkay($signature->getSummary())) {
			throw new SecurityTxtError(new SecurityTxtSignatureInvalid());
		}
		return new SecurityTxtSignatureVerifyResult($signature->getFingerprint(), (new DateTimeImmutable())->setTimestamp($signature->getTimestamp()));
	}


	private function isSignatureKindaOkay(int $summary): bool
	{
		return (($summary & GNUPG_SIGSUM_GREEN) !== 0 || ($summary & GNUPG_SIGSUM_KEY_MISSING) !== 0) && ($summary & GNUPG_SIGSUM_RED) === 0;
	}


	public function isClearsignHeader(string $line): bool
	{
		return $line === '-----BEGIN PGP SIGNED MESSAGE-----';
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
