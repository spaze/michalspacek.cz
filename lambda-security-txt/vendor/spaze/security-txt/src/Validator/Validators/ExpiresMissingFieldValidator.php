<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Validator\Validators;

use Override;
use Spaze\SecurityTxt\Exceptions\SecurityTxtError;
use Spaze\SecurityTxt\SecurityTxt;
use Spaze\SecurityTxt\Violations\SecurityTxtNoExpires;

final class ExpiresMissingFieldValidator implements FieldValidator
{

	/**
	 * @throws SecurityTxtError
	 */
	#[Override]
	public function validate(SecurityTxt $securityTxt): void
	{
		if ($securityTxt->getExpires() === null) {
			throw new SecurityTxtError(new SecurityTxtNoExpires());
		}
	}

}
