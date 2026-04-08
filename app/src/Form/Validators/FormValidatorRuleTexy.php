<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Form\Validators;

use MichalSpacekCz\Form\Validators\Exceptions\FormValidatorTexyFormatterErrorException;
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
	 * @return callable(TextBase): bool
	 */
	public function getRule(): callable
	{
		return function (TextBase $input): bool {
			try {
				$this->formValidatorTexyFormatter->format($input->value);
			} catch (FormValidatorTexyFormatterErrorException $e) {
				$input->addError($e->getMessage(), translate: false);
			}
			return true;
		};
	}

}
