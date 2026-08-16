<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Parser\FieldProcessors;

use Override;
use Spaze\SecurityTxt\Fields\SecurityTxtAcknowledgments;
use Spaze\SecurityTxt\SecurityTxt;

final class AcknowledgmentsAddFieldValue implements FieldProcessor
{

	#[Override]
	public function process(string $value, SecurityTxt $securityTxt): void
	{
		$acknowledgments = new SecurityTxtAcknowledgments($value);
		$securityTxt->addAcknowledgments($acknowledgments);
	}

}
