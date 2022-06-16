<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Training;

use MichalSpacekCz\Training\Exceptions\SpammyApplicationException;
use Nette\Http\SessionSection;
use stdClass;

class FormSpam
{

	/**
	 * Must be lowercase string, we need `ctype_lower()` to return true in case the field is missing.
	 */
	private const FIELD_MISSING_VALUE = 'missing';


	public function __construct(
		private readonly FormDataLogger $formDataLogger,
	) {
	}


	public function check(stdClass $values, string $name, ?SessionSection $sessionSection = null): void
	{
		if ($this->isSpam($values)) {
			$this->formDataLogger->log($values, $name, $sessionSection);
			throw new SpammyApplicationException();
		}
	}


	private function isSpam(stdClass $values): bool
	{
		if (preg_match('~\s+href="\s*https?://~', $values->note ?? '')) {
			return true;
		} elseif (
			ctype_lower($values->name ?? self::FIELD_MISSING_VALUE)
			&& ctype_lower($values->company ?? self::FIELD_MISSING_VALUE)
			&& ctype_lower($values->companyId ?? self::FIELD_MISSING_VALUE)
			&& ctype_lower($values->companyTaxId ?? self::FIELD_MISSING_VALUE)
		) {
			return true;
		}
		return false;
	}

}
