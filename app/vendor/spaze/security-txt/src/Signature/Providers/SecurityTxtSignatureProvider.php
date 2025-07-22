<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Signature\Providers;

use SensitiveParameter;
use Spaze\SecurityTxt\Signature\SecurityTxtSignatureErrorInfo;
use Spaze\SecurityTxt\Signature\SecurityTxtSignatureVerifySignatureInfo;

interface SecurityTxtSignatureProvider
{

	public function addSignKey(string $fingerprint, #[SensitiveParameter] string $passphrase = ''): bool;


	public function getErrorInfo(): SecurityTxtSignatureErrorInfo;


	public function sign(string $text): false|string;


	public function verify(string $text): SecurityTxtSignatureVerifySignatureInfo;

}
