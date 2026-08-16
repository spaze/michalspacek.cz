<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Signature\Exceptions;

use Throwable;

final class SecurityTxtSigningKeyNoPassphraseSetException extends SecurityTxtSignatureException
{

	public function __construct(string $key, ?Throwable $previous = null)
	{
		parent::__construct("Cannot create a signature, key {$key} requires a passphrase", previous: $previous);
	}

}
