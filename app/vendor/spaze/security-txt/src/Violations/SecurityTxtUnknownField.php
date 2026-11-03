<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Violations;

use Spaze\SecurityTxt\Fields\SecurityTxtRemovedField;

final class SecurityTxtUnknownField extends SecurityTxtSpecViolation
{

	public function __construct(string $fieldName, string $line, ?SecurityTxtRemovedField $removedField = null)
	{
		if ($removedField === SecurityTxtRemovedField::Signature) {
			$format = 'Field %s was removed in %s, the file itself is signed with an OpenPGP cleartext signature instead';
			$values = [$fieldName, 'draft-foudil-securitytxt-05'];
			$since = 'draft-foudil-securitytxt-05';
			$correctValue = null;
			$howToFix = "Remove the %s field, and sign the file with an OpenPGP cleartext signature if it isn't signed already";
			$specSection = '2.3';
		} else {
			$format = "Field %s is unknown, researchers ignore fields they don't support";
			$values = [$fieldName];
			$since = null;
			$correctValue = "# {$line}";
			$howToFix = "The %s field can stay, but if it's meant for researchers, use a registered field, or turn it into a comment";
			$specSection = '2.4';
		}
		parent::__construct(
			func_get_args(),
			$format,
			$values,
			$since,
			$correctValue,
			$howToFix,
			[$fieldName],
			$specSection,
		);
	}

}
