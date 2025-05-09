<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Parser\FieldProcessors;

use Override;
use Spaze\SecurityTxt\Fields\SecurityTxtEncryption;
use Spaze\SecurityTxt\SecurityTxt;

final class EncryptionAddFieldValue implements FieldProcessor
{

	#[Override]
	public function process(string $value, SecurityTxt $securityTxt): void
	{
		$encryption = new SecurityTxtEncryption($value);
		$securityTxt->addEncryption($encryption);
	}

}
