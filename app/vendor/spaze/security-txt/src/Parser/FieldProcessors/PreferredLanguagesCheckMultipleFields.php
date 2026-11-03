<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Parser\FieldProcessors;

use Override;
use Spaze\SecurityTxt\Exceptions\SecurityTxtError;
use Spaze\SecurityTxt\SecurityTxt;
use Spaze\SecurityTxt\Violations\SecurityTxtMultiplePreferredLanguages;

final class PreferredLanguagesCheckMultipleFields implements FieldProcessor
{

	/**
	 * The file the last Preferred-Languages field was in, because the `SecurityTxt` object has no Preferred-Languages value after one with a wrong separator
	 */
	private ?SecurityTxt $securityTxt = null;


	/**
	 * @throws SecurityTxtError
	 */
	#[Override]
	public function process(string $value, SecurityTxt $securityTxt): void
	{
		if ($this->securityTxt === $securityTxt) {
			throw new SecurityTxtError(new SecurityTxtMultiplePreferredLanguages());
		}
		$this->securityTxt = $securityTxt;
	}

}
