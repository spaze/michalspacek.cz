<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Form\Validators;

use Exception;
use MichalSpacekCz\Form\Validators\Exceptions\FormValidatorTexyFormatterErrorException;
use MichalSpacekCz\Formatter\TexyFormatter;
use Nette\Application\UI\InvalidLinkException;
use Nette\Utils\Html;

final readonly class FormValidatorTexyFormatter
{

	public function __construct(
		private TexyFormatter $texyFormatter,
	) {
	}


	/**
	 * @throws FormValidatorTexyFormatterErrorException
	 */
	public function format(mixed $value): ?Html
	{
		if (!is_string($value)) {
			return null;
		}
		try {
			// Use a fresh Texy instance to avoid stale internal status throwing "Processing is in progress" exception on next Texy render.
			// It's ok to format the same input multiple times, because TexyFormatter caches the output and uses the cache when needed.
			$texyFormatter = $this->texyFormatter->withTexy($this->texyFormatter->getTexy());
			return $texyFormatter->format($value);
		} catch (Exception $e) {
			$prefix = $e instanceof InvalidLinkException ? 'Invalid link' : $e::class;
			throw new FormValidatorTexyFormatterErrorException("$prefix: {$e->getMessage()}", previous: $e);
		}
	}

}
