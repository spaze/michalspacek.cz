<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Parser\FieldProcessors;

use Override;
use Spaze\SecurityTxt\Fields\SecurityTxtCsaf;
use Spaze\SecurityTxt\SecurityTxt;

final class CsafAddFieldValue implements FieldProcessor
{

	#[Override]
	public function process(string $value, SecurityTxt $securityTxt): void
	{
		$csaf = new SecurityTxtCsaf($value);
		$securityTxt->addCsaf($csaf);
	}

}
