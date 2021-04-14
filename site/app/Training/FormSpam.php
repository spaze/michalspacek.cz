<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Training;

use Nette\Utils\ArrayHash;

class FormSpam
{

	public function isSpam(ArrayHash $values): bool
	{
		if (preg_match('~\s+href="\s*https?://~', $values->note)) {
			return true;
		}
		return false;
	}

}
