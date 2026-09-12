<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Violations;

use Spaze\SecurityTxt\Fields\SecurityTxtField;

final class SecurityTxtPreferredLanguagesWrongLanguageTags extends SecurityTxtSpecViolation
{

	/**
	 * @param array<int, string> $wrongLanguages
	 */
	public function __construct(array $wrongLanguages)
	{
		$tagsValues = [];
		// `#%s` and not `#{$key}`, a runtime number written into the format would stop it being a `literal-string`
		foreach ($wrongLanguages as $key => $value) {
			$tagsValues[] = (string)$key;
			$tagsValues[] = $value;
		}
		$tags = $this->getRepeatedFormat('#%s %s', count($wrongLanguages));
		$format = count($wrongLanguages) > 1
			? 'The language tags ' . $tags . ' seem invalid, the %s field must contain one or more language tags as defined in RFC 5646'
			: 'The language tag ' . $tags . ' seems invalid, the %s field must contain one or more language tags as defined in RFC 5646';
		parent::__construct(
			func_get_args(),
			$format,
			[...$tagsValues, SecurityTxtField::PreferredLanguages->value],
			'draft-foudil-securitytxt-05',
			null,
			'Use language tags as defined in RFC 5646, which usually means the shortest ISO 639 code like for example %s',
			['en'],
			'2.5.8',
		);
	}

}
