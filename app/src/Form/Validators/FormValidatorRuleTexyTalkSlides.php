<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Form\Validators;

use Composer\Pcre\Regex;
use MichalSpacekCz\Form\Validators\Exceptions\FormValidatorTexyFormatterErrorException;
use Nette\Forms\Controls\TextBase;
use Nette\HtmlStringable;
use Override;
use Stringable;

/**
 * Checks whether a form field has a valid Texy content that can be rendered as a talk slide without any issues.
 *
 * Implements HtmlStringable in addition to Stringable, because while TextArea::addRule() accepts Stringable,
 * Validator::formatMessage() doesn't know how to process it, but knows about HtmlStringable.
 */
final class FormValidatorRuleTexyTalkSlides implements HtmlStringable, Stringable
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
				$html = $this->formValidatorTexyFormatter->format($input->value);
			} catch (FormValidatorTexyFormatterErrorException $e) {
				$this->message = $e->getMessage();
				return false;
			}
			if ($html !== null) {
				$result = Regex::matchStrictGroups('~</(ol|ul|blockquote|pre|table)>\Z~i', rtrim($html->render()));
				if ($result->matched) {
					$this->message = 'Text ends with ' . strtoupper($result->matches[1]) . ', but it should end with a paragraph, otherwise the slide number will be on a separate line';
					return false;
				}
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
