<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Validator\Validators;

use Spaze\SecurityTxt\Exceptions\SecurityTxtError;
use Spaze\SecurityTxt\Exceptions\SecurityTxtWarning;
use Spaze\SecurityTxt\SecurityTxt;

interface FieldValidator
{

	/**
	 * @throws SecurityTxtError|SecurityTxtWarning
	 */
	public function validate(SecurityTxt $securityTxt): void;

}
