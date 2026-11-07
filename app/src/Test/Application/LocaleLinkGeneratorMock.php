<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Test\Application;

use MichalSpacekCz\Application\Locale\LocaleLink;
use MichalSpacekCz\Application\Locale\LocaleLinkGenerator;
use MichalSpacekCz\Test\WillThrow;
use Override;

final class LocaleLinkGeneratorMock extends LocaleLinkGenerator
{

	use WillThrow;


	/** @var array<string, string> */
	private array $links = [];

	/** @var array<string, string> */
	private array $allLinks = [];

	/** @var array<string, string> */
	private array $linksForLocaleParams = [];

	/** @var array<string, list<string>|array<string, string|null>> */
	private array $allLinksParams = [];

	/** @var list<string> */
	private array $linksDestinations = [];

	/** @var list<string> */
	private array $linksForLocaleParamsDestinations = [];


	/**
	 * @param array<string, array{code:string, name:string}> $languages
	 * @noinspection PhpMissingParentConstructorInspection Intentionally
	 * @phpstan-ignore constructor.missingParentCall
	 */
	public function __construct(
		private readonly array $languages,
	) {
	}


	public function reset(): void
	{
		$this->links = [];
		$this->allLinks = [];
		$this->allLinksParams = [];
		$this->linksForLocaleParams = [];
		$this->linksDestinations = [];
		$this->linksForLocaleParamsDestinations = [];
		$this->wontThrow();
	}


	/**
	 * @param array<string, string> $links
	 */
	public function setLinks(array $links): void
	{
		$this->links = $links;
	}


	#[Override]
	public function links(string $destination, array $params = []): array
	{
		$this->linksDestinations[] = $destination;
		$links = [];
		foreach ($this->links as $locale => $link) {
			$links[$locale] = new LocaleLink($locale, $this->languages[$locale]['code'], $this->languages[$locale]['name'], $link);
		}
		return $links;
	}


	/**
	 * @return list<string>
	 */
	public function getLinksDestinations(): array
	{
		return $this->linksDestinations;
	}


	/**
	 * The links to return, only for the locales given to linksForLocaleParams() like the real one does.
	 *
	 * @param array<string, string> $linksForLocaleParams locale => URL
	 */
	public function setLinksForLocaleParams(array $linksForLocaleParams): void
	{
		$this->linksForLocaleParams = $linksForLocaleParams;
	}


	#[Override]
	public function linksForLocaleParams(string $destination, array $localeParams): array
	{
		$this->linksForLocaleParamsDestinations[] = $destination;
		$links = [];
		foreach ($this->linksForLocaleParams as $locale => $link) {
			if (isset($localeParams[$locale]) || isset($localeParams['*'])) {
				$links[$locale] = new LocaleLink($locale, $this->languages[$locale]['code'], $this->languages[$locale]['name'], $link);
			}
		}
		return $links;
	}


	/**
	 * @return list<string>
	 */
	public function getLinksForLocaleParamsDestinations(): array
	{
		return $this->linksForLocaleParamsDestinations;
	}


	#[Override]
	public function defaultParams(array $params): array
	{
		return ['*' => $params];
	}


	#[Override]
	public function setDefaultParams(array &$params, array $defaultParams): void
	{
		$params['*'] = $defaultParams;
	}


	/**
	 * @param array<string, string> $allLinks
	 */
	public function setAllLinks(array $allLinks): void
	{
		$this->allLinks = $allLinks;
	}


	#[Override]
	public function allLinks(string $destination, array $params = []): array
	{
		$this->maybeThrow();
		$this->allLinksParams = $params;
		return $this->allLinks;
	}


	/**
	 * @return array<string, list<string>|array<string, string|null>>
	 */
	public function getAllLinksParams(): array
	{
		return $this->allLinksParams;
	}

}
