<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Validator\Validators;

use Override;
use Spaze\SecurityTxt\Exceptions\SecurityTxtWarning;
use Spaze\SecurityTxt\SecurityTxt;
use Spaze\SecurityTxt\Violations\SecurityTxtSignedButNoCanonical;

final class SignedButCanonicalMissingFieldValidator implements FieldValidator
{

	/**
	 * @throws SecurityTxtWarning
	 */
	#[Override]
	public function validate(SecurityTxt $securityTxt): void
	{
		if ($securityTxt->getSignatureVerifyResult() !== null && $securityTxt->getCanonical() === []) {
			throw new SecurityTxtWarning(new SecurityTxtSignedButNoCanonical());
		}
	}

}
