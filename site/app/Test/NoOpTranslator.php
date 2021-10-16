<?php
/** @noinspection PhpMissingParentConstructorInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\Test;

use Contributte\Translation\Translator;

class NoOpTranslator extends Translator
{

	public function __construct()
	{
	}


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
