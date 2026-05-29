<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Form\Validators;

use MichalSpacekCz\Form\Validators\Exceptions\FormValidatorTexyFormatterErrorException;
use Nette\Forms\Control;
use Nette\Forms\Controls\TextBase;

/**
 * Checks whether a form field has a valid Texy content that can be rendered without any issues.
 */
final class FormValidatorRuleTexy
{

	public function __construct(
		private readonly FormValidatorTexyFormatter $formValidatorTexyFormatter,
	) {
	}


	/**
	 * @return callable(Control): bool
	 */
	public function getRule(): callable
	{
		return function (Control $input): true {
			if (!$input instanceof TextBase) {
				return true;
			}
			try {
				$this->formValidatorTexyFormatter->format($input->value);
			} catch (FormValidatorTexyFormatterErrorException $e) {
				$input->addError($e->getMessage(), translate: false);
			}
			return true;
		};
	}

}
