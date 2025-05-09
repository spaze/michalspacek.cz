<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Violations;

final class SecurityTxtPreferredLanguagesSeparatorNotComma extends SecurityTxtSpecViolation
{

	/**
	 * @param array<int, string> $wrongSeparators
	 * @param list<string> $languages
	 */
	public function __construct(array $wrongSeparators, array $languages)
	{
		$separators = [];
		foreach ($wrongSeparators as $number => $separator) {
			$separators[] = "#{$number} `{$separator}`";
		}
		$message = count($wrongSeparators) > 1
			? 'The `Preferred-Languages` field uses wrong separators (%s), separate multiple values with a comma (`,`)'
			: 'The `Preferred-Languages` field uses a wrong separator (%s), separate multiple values with a comma (`,`)';
		parent::__construct(
			func_get_args(),
			$message,
			[implode(', ', $separators)],
			'draft-foudil-securitytxt-05',
			implode(', ', $languages),
			'Use comma (`,`) to list multiple languages in the `Preferred-Languages` field',
			[],
			'2.5.8',
		);
	}

}
