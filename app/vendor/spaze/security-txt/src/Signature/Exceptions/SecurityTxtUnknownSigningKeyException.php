<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Signature\Exceptions;

use Throwable;

final class SecurityTxtUnknownSigningKeyException extends SecurityTxtSignatureException
{

	public function __construct(string $key, ?Throwable $previous = null)
	{
		parent::__construct("Cannot create a signature, unknown key {$key}", previous: $previous);
	}

}
