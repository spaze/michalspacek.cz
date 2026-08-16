<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Parser\FieldProcessors;

use Override;
use Spaze\SecurityTxt\Exceptions\SecurityTxtError;
use Spaze\SecurityTxt\Fields\SecurityTxtCanonical;
use Spaze\SecurityTxt\SecurityTxt;

final class CanonicalAddFieldValue implements FieldProcessor
{

	/**
	 * @throws SecurityTxtError
	 */
	#[Override]
	public function process(string $value, SecurityTxt $securityTxt): void
	{
		$canonical = new SecurityTxtCanonical($value);
		$securityTxt->addCanonical($canonical);
	}

}
