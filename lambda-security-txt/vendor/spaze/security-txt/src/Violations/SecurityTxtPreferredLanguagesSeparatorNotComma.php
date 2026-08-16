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
		$separators = $separatorsValues = [];
		foreach ($wrongSeparators as $number => $separator) {
			$separators[] = "#{$number} %s";
			$separatorsValues[] = $separator;
		}
		$message = count($wrongSeparators) > 1
			? 'The %s field uses wrong separators (' . implode(', ', $separators) . '), separate multiple values with a comma (%s)'
			: 'The %s field uses a wrong separator (' . implode(', ', $separators) . '), separate multiple values with a comma (%s)';
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
