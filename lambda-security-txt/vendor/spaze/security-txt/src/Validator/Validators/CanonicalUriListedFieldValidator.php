<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Validator\Validators;

use Override;
use Spaze\SecurityTxt\Exceptions\SecurityTxtWarning;
use Spaze\SecurityTxt\SecurityTxt;
use Spaze\SecurityTxt\Violations\SecurityTxtCanonicalUriMismatch;

final class CanonicalUriListedFieldValidator implements FieldValidator
{

	#[Override]
	public function validate(SecurityTxt $securityTxt): void
	{
		$uri = $securityTxt->getFileLocation();
		if ($uri === null) {
			return;
		}

		$canonicals = $securityTxt->getCanonical();
		if ($canonicals === []) {
			return;
		}

		$canonicalUris = array_map(fn($canonical) => $canonical->getUri(), $canonicals);
		if (!in_array($uri, $canonicalUris, true)) {
			throw new SecurityTxtWarning(new SecurityTxtCanonicalUriMismatch($uri, $canonicalUris));
		}
	}

}
