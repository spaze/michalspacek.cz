<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Violations;

/**
 * Why a language tag is a common mistake. The reason used to be a `literal-string` parameter, which holds for code but not for a violation rebuilt from JSON, where the string
 * comes from whatever the JSON says: as an enum, the wire carries only the case value, the format comes from a `match` here, and a value that is not a case fails `from()`
 * inside the guard in `SecurityTxtJson`, so the format cannot be forged.
 */
enum SecurityTxtPreferredLanguagesCommonMistakeReason: string
{

	case CzechUsesCsNotCz = 'czech-uses-cs-not-cz';


	/**
	 * @return literal-string
	 */
	public function getFormat(): string
	{
		return match ($this) {
			self::CzechUsesCsNotCz => 'the code for Czech language is %s, not %s',
		};
	}


	/**
	 * The values the format above expects, kept next to it because the two have to agree on how many placeholders there are, and only together here they always do.
	 *
	 * @return list<string>
	 */
	public function getValues(): array
	{
		return match ($this) {
			self::CzechUsesCsNotCz => ['cs', 'cz'],
		};
	}

}
