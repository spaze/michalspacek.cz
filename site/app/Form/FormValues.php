<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Form;

use MichalSpacekCz\ShouldNotHappenException;
use Nette\ComponentModel\Component;
use Nette\Forms\Controls\BaseControl;
use Nette\Forms\Form;
use Nette\InvalidStateException;
use Nette\Utils\ArrayHash;

class FormValues
{

	/**
	 * @throws ShouldNotHappenException
	 */
	public function getValues(BaseControl $control): ArrayHash
	{
		return $this->getForm($control)->getValues();
	}


	/**
	 * @throws ShouldNotHappenException
	 */
	public function getUntrustedValues(BaseControl $control): ArrayHash
	{
		$values = $this->getForm($control)->getUntrustedValues(null);
		if (!$values instanceof ArrayHash) {
			throw new ShouldNotHappenException();
		}
		return $values;
	}


	/**
	 * @throws ShouldNotHappenException
	 */
	private function getForm(BaseControl $control): Form
	{
		$form = $control->getForm();
		if (!$form) {
			throw new ShouldNotHappenException(sprintf('%s would be thrown already in %s::lookup()', InvalidStateException::class, Component::class));
		}
		return $form;
	}

}
