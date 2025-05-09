<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Parser\FieldProcessors;

use Override;
use Spaze\SecurityTxt\Exceptions\SecurityTxtError;
use Spaze\SecurityTxt\SecurityTxt;
use Spaze\SecurityTxt\Violations\SecurityTxtMultipleExpires;

final class ExpiresCheckMultipleFields implements FieldProcessor
{

	/**
	 * @throws SecurityTxtError
	 */
	#[Override]
	public function process(string $value, SecurityTxt $securityTxt): void
	{
		if ($securityTxt->getExpires() !== null) {
			throw new SecurityTxtError(new SecurityTxtMultipleExpires());
		}
	}

}
