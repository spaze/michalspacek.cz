<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Form\Validators;

use Exception;
use MichalSpacekCz\Formatter\TexyFormatter;
use Nette\Application\UI\InvalidLinkException;
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
final class FormValidatorTexyRule implements HtmlStringable, Stringable
{

	private string $message = '';


	public function __construct(
		private readonly TexyFormatter $texyFormatter,
	) {
	}


	/**
	 * @return callable(TextBase): bool
	 */
	public function getRule(): callable
	{
		return function (TextBase $input): bool {
			if (!is_string($input->value)) {
				return true;
			}
			try {
				// Use a fresh Texy instance to avoid stale internal status throwing "Processing is in progress" exception on next Texy render
				$this->texyFormatter->withTexy($this->texyFormatter->getTexy())->format($input->value);
			} catch (Exception $e) {
				$this->message = ($e instanceof InvalidLinkException ? 'Invalid link: ' : $e::class . ': ') . $e->getMessage();
				return false;
			}
			return true;
		};
	}


	#[Override]
	public function __toString(): string
	{
		return $this->message;
	}

}
