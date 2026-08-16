<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Fields;

use Override;

final class SecurityTxtCsaf extends SecurityTxtUriField implements SecurityTxtFieldValue
{

	public const string METADATA_FILENAME = 'provider-metadata.json';


	#[Override]
	public function getField(): SecurityTxtField
	{
		return SecurityTxtField::Csaf;
	}

}
