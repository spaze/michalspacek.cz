<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Violations;

use Spaze\SecurityTxt\Fields\SecurityTxtField;

final class SecurityTxtPreferredLanguagesSeparatorNotComma extends SecurityTxtSpecViolation
{

	/**
	 * @param array<int, string> $wrongSeparators
	 * @param list<string> $languages
	 */
	public function __construct(array $wrongSeparators, array $languages)
	{
		$separatorsValues = [];
		// `#%s` and not `#{$number}`, a runtime number written into the format would stop it being a `literal-string`
		foreach ($wrongSeparators as $number => $separator) {
			$separatorsValues[] = (string)$number;
			$separatorsValues[] = $separator;
		}
		$separators = $this->getRepeatedFormat('#%s %s', count($wrongSeparators));
		$message = count($wrongSeparators) > 1
			? 'The %s field uses wrong separators (' . $separators . '), separate multiple values with a comma (%s)'
			: 'The %s field uses a wrong separator (' . $separators . '), separate multiple values with a comma (%s)';
		parent::__construct(
			func_get_args(),
			$message,
			[SecurityTxtField::PreferredLanguages->value, ...$separatorsValues, ','],
			'draft-foudil-securitytxt-05',
			implode(', ', $languages),
			'Use comma (%s) to list multiple languages in the %s field',
			[',', SecurityTxtField::PreferredLanguages->value],
			'2.5.8',
		);
	}

}
