<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Form\Validators;

use Composer\Pcre\Regex;
use MichalSpacekCz\Form\Validators\Exceptions\FormValidatorTexyFormatterErrorException;
use Nette\Forms\Control;
use Nette\Forms\Controls\TextBase;

/**
 * Checks whether a form field has a valid Texy content that can be rendered as a talk slide without any issues.
 */
final class FormValidatorRuleTexyTalkSlides
{

	public function __construct(
		private readonly FormValidatorTexyFormatter $formValidatorTexyFormatter,
	) {
	}


	/**
	 * @return callable(Control): true
	 */
	public function getRule(): callable
	{
		return function (Control $input): true {
			if (!$input instanceof TextBase) {
				return true;
			}
			try {
				$html = $this->formValidatorTexyFormatter->format($input->value);
			} catch (FormValidatorTexyFormatterErrorException $e) {
				$input->addError($e->getMessage(), translate: false);
				return true;
			}
			if ($html !== null) {
				$result = Regex::matchStrictGroups('~</(ol|ul|blockquote|pre|table)>\Z~i', rtrim($html->render()));
				if ($result->matched) {
					$input->addError('Text ends with ' . strtoupper($result->matches[1]) . ', but it should end with a paragraph, otherwise the slide number will be on a separate line', translate: false);
				}
			}
			return true;
		};
	}

}
