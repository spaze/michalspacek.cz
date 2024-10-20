<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Form;

use MichalSpacekCz\ShouldNotHappenException;
use Nette\Application\UI\Form;
use Nette\Utils\ArrayHash;
use Override;

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


	/**
	 * @return object|array<string, mixed>
	 * @deprecated Use getFormValues() instead
	 */
	#[Override]
	public function getValues(string|object|bool|null $returnType = null, ?array $controls = null): object|array
	{
		if (func_num_args() === 0) {
			trigger_error('Use getFormValues() instead', E_USER_DEPRECATED);
		}
		return parent::getValues($returnType, $controls);
	}


	/**
	 * @param string|object|null $returnType
	 * @return object|array<string, mixed>
	 * @deprecated Use getUntrustedFormValues() instead
	 * */
	#[Override]
	public function getUntrustedValues($returnType = ArrayHash::class, ?array $controls = null): object|array
	{
		if (func_num_args() === 0) {
			trigger_error('Use getUntrustedFormValues() instead', E_USER_DEPRECATED);
		}
		return parent::getUntrustedValues($returnType, $controls);
	}

}
