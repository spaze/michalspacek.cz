<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Form\Validators;

use MichalSpacekCz\Form\Validators\Exceptions\FormValidatorTexyFormatterErrorException;
use Nette\Forms\Controls\TextBase;
use Nette\HtmlStringable;
use Override;
use Stringable;

/**
 * Checks whether a form field has a valid Texy content that can be rendered without any issues.
 *
 * Implements HtmlStringable in addition to Stringable, because while TextArea::addRule() accepts Stringable,
 * Validator::formatMessage() doesn't know how to process it, but knows about HtmlStringable.
 */
final class FormValidatorRuleTexy implements HtmlStringable, Stringable
{

	private string $message = '';


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
			$this->message = '';
			try {
				$this->formValidatorTexyFormatter->format($input->value);
			} catch (FormValidatorTexyFormatterErrorException $e) {
				$this->message = $e->getMessage();
				return false;
			}
			return true;
		};
	}


	public function getMessage(): self
	{
		return $this;
	}


	#[Override]
	public function __toString(): string
	{
		return $this->message;
	}

}
