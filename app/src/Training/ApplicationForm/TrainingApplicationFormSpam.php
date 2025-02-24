<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Training\ApplicationForm;

use Composer\Pcre\Regex;
use MichalSpacekCz\Training\Exceptions\SpammyApplicationException;

final class TrainingApplicationFormSpam
{

	/**
	 * Must be lowercase string, we need `ctype_lower()` to return true in case the field is missing.
	 */
	private const string FIELD_MISSING_VALUE = 'missing';


	/**
	 * @throws SpammyApplicationException
	 */
	public function check(string $name, ?string $company = null, ?string $companyId = null, ?string $companyTaxId = null, ?string $note = null): void
	{
		if (Regex::isMatch('~\s+href="\s*https?://~', $note ?? self::FIELD_MISSING_VALUE)) {
			throw new SpammyApplicationException();
		} elseif (
			ctype_lower($name)
			&& ctype_lower($company ?? self::FIELD_MISSING_VALUE)
			&& ctype_lower($companyId ?? self::FIELD_MISSING_VALUE)
			&& ctype_lower($companyTaxId ?? self::FIELD_MISSING_VALUE)
		) {
			throw new SpammyApplicationException();
		}
	}

}
