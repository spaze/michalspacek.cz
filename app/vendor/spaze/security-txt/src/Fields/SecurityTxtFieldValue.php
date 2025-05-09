<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Fields;

interface SecurityTxtFieldValue
{

	public function getField(): SecurityTxtField;


	public function getValue(): string;

}
