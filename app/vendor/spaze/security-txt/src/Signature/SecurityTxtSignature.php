<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Signature;

use DateTimeImmutable;
use gnupg;
use Spaze\SecurityTxt\Exceptions\SecurityTxtError;
use Spaze\SecurityTxt\Exceptions\SecurityTxtWarning;
use Spaze\SecurityTxt\Signature\Exceptions\SecurityTxtCannotVerifySignatureException;
use Spaze\SecurityTxt\Violations\SecurityTxtSignatureExtensionNotLoaded;
use Spaze\SecurityTxt\Violations\SecurityTxtSignatureInvalid;

final readonly class SecurityTxtSignature
{

	public function __construct(
		private ?string $homeDir = null,
	) {
	}


	/**
	 * @throws SecurityTxtError
	 * @throws SecurityTxtWarning
	 * @throws SecurityTxtCannotVerifySignatureException
	 */
	public function verify(string $contents): SecurityTxtSignatureVerifyResult
	{
		if (!extension_loaded('gnupg')) {
			throw new SecurityTxtWarning(new SecurityTxtSignatureExtensionNotLoaded());
		}
		$options = $this->homeDir !== null ? ['home_dir' => $this->homeDir] : [];
		$signatures = new gnupg($options)->verify($contents, false);
		if ($signatures === false || !isset($signatures[0])) {
			throw new SecurityTxtError(new SecurityTxtSignatureInvalid());
		}
		$signature = $signatures[0];
		if (!is_array($signature)) {
			throw new SecurityTxtCannotVerifySignatureException('signature is not an array');
		}
		if (!isset($signature['summary']) || !is_int($signature['summary'])) {
			throw new SecurityTxtCannotVerifySignatureException('summary is missing or not a string');
		}
		if (!$this->isSignatureKindaOkay($signature['summary'])) {
			throw new SecurityTxtError(new SecurityTxtSignatureInvalid());
		}
		if (!isset($signature['fingerprint']) || !is_string($signature['fingerprint'])) {
			throw new SecurityTxtCannotVerifySignatureException('fingerprint is missing or not a string');
		}
		if (!isset($signature['timestamp']) || !is_int($signature['timestamp'])) {
			throw new SecurityTxtCannotVerifySignatureException('timestamp is missing or not a string');
		}
		return new SecurityTxtSignatureVerifyResult($signature['fingerprint'], new DateTimeImmutable()->setTimestamp($signature['timestamp']));
	}


	private function isSignatureKindaOkay(int $summary): bool
	{
		return (($summary & GNUPG_SIGSUM_GREEN) !== 0 || ($summary & GNUPG_SIGSUM_KEY_MISSING) !== 0) && ($summary & GNUPG_SIGSUM_RED) === 0;
	}


	public function isCleartextHeader(string $line): bool
	{
		return $line === '-----BEGIN PGP SIGNED MESSAGE-----';
	}

}
