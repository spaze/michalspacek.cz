<?php
/** @noinspection PhpMissingParentConstructorInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\Test;

use Contributte\Translation\Translator;

class NoOpTranslator extends Translator
{

	private string $defaultLocale;


	public function __construct()
	{
	}


	public function setDefaultLocale(string $defaultLocale): void
	{
		$this->defaultLocale = $defaultLocale;
	}


	public function getDefaultLocale(): string
	{
		return $this->defaultLocale;
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
