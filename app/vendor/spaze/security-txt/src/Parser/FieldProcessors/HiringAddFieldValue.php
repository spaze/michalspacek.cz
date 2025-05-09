<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Parser\FieldProcessors;

use Override;
use Spaze\SecurityTxt\Fields\SecurityTxtHiring;
use Spaze\SecurityTxt\SecurityTxt;

final class HiringAddFieldValue implements FieldProcessor
{

	#[Override]
	public function process(string $value, SecurityTxt $securityTxt): void
	{
		$hiring = new SecurityTxtHiring($value);
		$securityTxt->addHiring($hiring);
	}

}
