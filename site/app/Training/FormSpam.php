<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Training;

use stdClass;

class FormSpam
{

	public function isSpam(stdClass $values): bool
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
