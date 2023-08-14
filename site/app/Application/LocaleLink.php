<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Application;

class LocaleLink
{

	public function __construct(
		private readonly string $locale,
		private readonly string $languageCode,
		private readonly string $languageName,
		private readonly string $url,
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
