<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Application\Locale;

readonly class LocaleLink
{

	public function __construct(
		private string $locale,
		private string $languageCode,
		private string $languageName,
		private string $url,
	) {
	}


	public function getLocale(): string
	{
		return $this->locale;
	}


	public function getLanguageCode(): string
	{
		return $this->languageCode;
	}


	public function getLanguageName(): string
	{
		return $this->languageName;
	}


	public function getUrl(): string
	{
		return $this->url;
	}


	public function withUrl(string $url): self
	{
		return new self(
			$this->getLocale(),
			$this->getLanguageCode(),
			$this->getLanguageName(),
			$url,
		);
	}

}
