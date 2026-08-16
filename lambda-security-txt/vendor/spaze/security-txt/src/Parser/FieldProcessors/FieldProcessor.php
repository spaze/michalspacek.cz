<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Parser\FieldProcessors;

use Spaze\SecurityTxt\Exceptions\SecurityTxtError;
use Spaze\SecurityTxt\Exceptions\SecurityTxtWarning;
use Spaze\SecurityTxt\SecurityTxt;

interface FieldProcessor
{

	/**
	 * @throws SecurityTxtError|SecurityTxtWarning
	 */
	public function process(string $value, SecurityTxt $securityTxt): void;

}
