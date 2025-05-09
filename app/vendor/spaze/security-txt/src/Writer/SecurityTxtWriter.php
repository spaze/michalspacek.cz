<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Writer;

use Spaze\SecurityTxt\SecurityTxt;

final class SecurityTxtWriter
{

	public function write(SecurityTxt $securityTxt): string
	{
		$result = '';
		foreach ($securityTxt->getOrderedFields() as $field) {
			$result .= sprintf("%s: %s\n", $field->getField()->value, $field->getValue());
		}
		return $result;
	}

}
