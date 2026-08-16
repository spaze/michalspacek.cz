<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Signature\Exceptions;

use Throwable;

final class SecurityTxtCannotCreateSignatureExtensionNotLoadedException extends SecurityTxtSignatureException
{

	public function __construct(?Throwable $previous = null)
	{
		parent::__construct('Cannot create a signature, the gnupg extension is not loaded', previous: $previous);
	}

}
