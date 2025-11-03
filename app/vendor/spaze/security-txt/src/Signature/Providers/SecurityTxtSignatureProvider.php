<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Signature\Providers;

use SensitiveParameter;
use Spaze\SecurityTxt\Signature\Exceptions\SecurityTxtCannotCreateSignatureExtensionNotLoadedException;
use Spaze\SecurityTxt\Signature\Exceptions\SecurityTxtCannotVerifySignatureException;
use Spaze\SecurityTxt\Signature\SecurityTxtSignatureErrorInfo;
use Spaze\SecurityTxt\Signature\SecurityTxtSignatureVerifySignatureInfo;

interface SecurityTxtSignatureProvider
{

	/**
	 * @throws SecurityTxtCannotCreateSignatureExtensionNotLoadedException
	 */
	public function addSignKey(string $fingerprint, #[SensitiveParameter] string $passphrase = ''): bool;


	/**
	 * @throws SecurityTxtCannotCreateSignatureExtensionNotLoadedException
	 */
	public function getErrorInfo(): SecurityTxtSignatureErrorInfo;


	/**
	 * @throws SecurityTxtCannotCreateSignatureExtensionNotLoadedException
	 */
	public function sign(string $text): false|string;


	/**
	 * @throws SecurityTxtCannotCreateSignatureExtensionNotLoadedException
	 * @throws SecurityTxtCannotVerifySignatureException
	 */
	public function verify(string $text): SecurityTxtSignatureVerifySignatureInfo;

}
