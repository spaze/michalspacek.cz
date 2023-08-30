<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Training\ApplicationForm;

use MichalSpacekCz\Training\Exceptions\SpammyApplicationException;
use stdClass;

class TrainingApplicationFormSpam
{

	/**
	 * Must be lowercase string, we need `ctype_lower()` to return true in case the field is missing.
	 */
	private const FIELD_MISSING_VALUE = 'missing';


	public function check(stdClass $values): void
	{
		if (preg_match('~\s+href="\s*https?://~', $values->note ?? '')) {
			throw new SpammyApplicationException();
		} elseif (
			ctype_lower($values->name ?? self::FIELD_MISSING_VALUE)
			&& ctype_lower($values->company ?? self::FIELD_MISSING_VALUE)
			&& ctype_lower($values->companyId ?? self::FIELD_MISSING_VALUE)
			&& ctype_lower($values->companyTaxId ?? self::FIELD_MISSING_VALUE)
		) {
			throw new SpammyApplicationException();
		}
	}

}
