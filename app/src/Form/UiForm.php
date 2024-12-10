<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Form;

use MichalSpacekCz\ShouldNotHappenException;
use Nette\Application\UI\Form;
use Nette\Utils\ArrayHash;

class UiForm extends Form
{

	public function getFormValues(): ArrayHash
	{
		$values = parent::getValues();
		if (!$values instanceof ArrayHash) {
			throw new ShouldNotHappenException();
		}
		return $values;
	}


	public function getUntrustedFormValues(): ArrayHash
	{
		$values = parent::getUntrustedValues();
		if (!$values instanceof ArrayHash) {
			throw new ShouldNotHappenException();
		}
		return $values;
	}

}
