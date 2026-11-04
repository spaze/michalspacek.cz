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
	 * The file the last Expires field was in, because the `SecurityTxt` object has no Expires value after one whose value isn't a date
	 */
	private ?SecurityTxt $securityTxt = null;


	/**
	 * @throws SecurityTxtError
	 */
	#[Override]
	public function process(string $value, SecurityTxt $securityTxt): void
	{
		if ($this->securityTxt === $securityTxt) {
			throw new SecurityTxtError(new SecurityTxtMultipleExpires());
		}
		$this->securityTxt = $securityTxt;
	}

}
