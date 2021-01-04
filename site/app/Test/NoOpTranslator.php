<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Test;

use Nette\Localization\Translator;

class NoOpTranslator implements Translator
{

	public function translate($message, ...$parameters): string
	{
		return $message;
	}


	/**
	 * @return string[]
	 */
	public function getAvailableLocales(): array
	{
		return [];
	}

}
