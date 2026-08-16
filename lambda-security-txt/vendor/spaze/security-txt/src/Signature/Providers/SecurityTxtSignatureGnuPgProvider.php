<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Signature\Providers;

use gnupg;
use Override;
use SensitiveParameter;
use Spaze\SecurityTxt\Signature\Exceptions\SecurityTxtCannotCreateSignatureExtensionNotLoadedException;
use Spaze\SecurityTxt\Signature\Exceptions\SecurityTxtCannotVerifySignatureException;
use Spaze\SecurityTxt\Signature\SecurityTxtSignatureErrorInfo;
use Spaze\SecurityTxt\Signature\SecurityTxtSignatureVerifySignatureInfo;

final class SecurityTxtSignatureGnuPgProvider implements SecurityTxtSignatureProvider
{

	private ?gnupg $gnupg = null;


	public function __construct(private readonly ?string $homeDir = null)
	{
	}


	#[Override]
	public function addSignKey(string $fingerprint, #[SensitiveParameter] string $passphrase = ''): bool
	{
		return $this->getGnuPg()->addsignkey($fingerprint, $passphrase);
	}


	#[Override]
	public function getErrorInfo(): SecurityTxtSignatureErrorInfo
	{
		$error = $this->getGnuPg()->geterrorinfo();
		return new SecurityTxtSignatureErrorInfo(
			is_string($error['generic_message']) || $error['generic_message'] === false ? $error['generic_message'] : null,
			is_int($error['gpgme_code']) ? $error['gpgme_code'] : null,
			is_string($error['gpgme_source']) ? $error['gpgme_source'] : null,
			is_string($error['gpgme_message']) ? $error['gpgme_message'] : null,
		);
	}


	#[Override]
	public function sign(string $text): false|string
	{
		return $this->getGnuPg()->sign($text);
	}


	#[Override]
	public function verify(string $text): SecurityTxtSignatureVerifySignatureInfo
	{
		$result = $this->getGnuPg()->verify($text, false);
		if ($result === false || !isset($result[0])) {
			throw new SecurityTxtCannotVerifySignatureException(null, $this->getErrorInfo());
		}
		$signature = $result[0];
		if (!is_array($signature)) {
			throw new SecurityTxtCannotVerifySignatureException('signature is not an array', $this->getErrorInfo());
		}
		if (!isset($signature['summary']) || !is_int($signature['summary'])) {
			throw new SecurityTxtCannotVerifySignatureException('summary is missing or not a string', $this->getErrorInfo());
		}
		if (!isset($signature['fingerprint']) || !is_string($signature['fingerprint'])) {
			throw new SecurityTxtCannotVerifySignatureException('fingerprint is missing or not a string', $this->getErrorInfo());
		}
		if (!isset($signature['timestamp']) || !is_int($signature['timestamp'])) {
			throw new SecurityTxtCannotVerifySignatureException('timestamp is missing or not a string', $this->getErrorInfo());
		}
		return new SecurityTxtSignatureVerifySignatureInfo($signature['summary'], $signature['fingerprint'], $signature['timestamp']);
	}


	/**
	 * @throws SecurityTxtCannotCreateSignatureExtensionNotLoadedException
	 */
	private function getGnuPg(): gnupg
	{
		if (!extension_loaded('gnupg')) {
			throw new SecurityTxtCannotCreateSignatureExtensionNotLoadedException();
		}
		if ($this->gnupg === null) {
			$options = $this->homeDir !== null ? ['home_dir' => $this->homeDir] : [];
			$this->gnupg = new gnupg($options);
		}
		return $this->gnupg;
	}

}
