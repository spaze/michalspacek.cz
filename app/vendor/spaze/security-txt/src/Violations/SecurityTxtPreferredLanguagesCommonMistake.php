<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Violations;

final class SecurityTxtPreferredLanguagesCommonMistake extends SecurityTxtSpecViolation
{

	/**
	 * @param list<string> $reasonValues
	 */
	public function __construct(int $position, string $mistake, ?string $correctValue, string $reason, array $reasonValues)
	{
		parent::__construct(
			func_get_args(),
			"The language tag #{$position} %s in the %s field is not correct, {$reason}",
			[$mistake, 'Preferred-Languages', ...$reasonValues],
			'draft-foudil-securitytxt-05',
			$correctValue,
			'Use language tags as defined in RFC 5646, which usually means the shortest ISO 639 code',
			[],
			'2.5.8',
		);
	}

}
