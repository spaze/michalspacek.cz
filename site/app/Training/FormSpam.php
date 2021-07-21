<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Training;

use Nette\Http\SessionSection;
use stdClass;
use UnexpectedValueException;

class FormSpam
{


	private FormDataLogger $formDataLogger;


	public function __construct(FormDataLogger $formDataLogger)
	{
		$this->formDataLogger = $formDataLogger;
	}


	public function check(stdClass $values, string $name, SessionSection $sessionSection): void
	{
		if ($this->isSpam($values)) {
			$this->formDataLogger->log($values, $name, $sessionSection);
			throw new UnexpectedValueException('Spammy application');
		}
	}


	private function isSpam(stdClass $values): bool
	{
		if (preg_match('~\s+href="\s*https?://~', $values->note ?? '')) {
			return true;
		} elseif (
			ctype_lower($values->name ?? '')
			&& ctype_lower($values->company ?? '')
			&& ctype_lower($values->companyId ?? '')
			&& ctype_lower($values->companyTaxId ?? '')
		) {
			return true;
		}
		return false;
	}

}
