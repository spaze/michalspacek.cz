<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Application\Locale;

use Contributte\Translation\Translator;
use MichalSpacekCz\Application\Routing\LocaleRouter;
use MichalSpacekCz\ShouldNotHappenException;
use Nette\Application\IPresenterFactory;
use Nette\Application\LinkGenerator;
use Nette\Application\Routers\RouteList;
use Nette\Application\UI\InvalidLinkException;
use Nette\Http\IRequest;

/**
 * Generates links to locales other than current.
 */
class LocaleLinkGenerator
{

	private const string DEFAULT_PARAMS = '*';


	/**
	 * @param array<string, array{code:string, name:string}> $languages
	 */
	public function __construct(
		private readonly LocaleRouter $localeRouter,
		private readonly IRequest $httpRequest,
		private readonly IPresenterFactory $presenterFactory,
		private readonly LinkGenerator $linkGenerator,
		private readonly Translator $translator,
		private readonly array $languages,
	) {
	}


	/**
	 * Generates localized URLs.
	 *
	 * @param string $destination destination in format "[[[module:]presenter:]action] [#fragment]"
	 * @param array<string, list<string>|array<array-key, mixed>> $params of locale => [position|name => value]
	 * @return array<string, LocaleLink> of locale => URL
	 * @throws InvalidLinkException
	 */
	public function links(string $destination, array $params = []): array
	{
		$links = [];
		foreach ($this->getOtherLocaleLinkGenerators() as $locale => $linkGenerator) {
			$links[$locale] = $this->createLocaleLink($locale, $linkGenerator->link($destination, $this->getParams($params, $locale)));
		}
		return $links;
	}


	/**
	 * Generates the URLs of a page in the locales it exists in, the current locale included. Unlike links(),
	 * the locales are only the keys of $localeParams, with the default params standing for all the locales of
	 * the destination's module, so a page of a module served in one locale, like Pulse, has just the one link.
	 *
	 * @param string $destination destination in format "[[[module:]presenter:]action] [#fragment]"
	 * @param array<string, array<array-key, mixed>> $localeParams of locale => [position|name => value]
	 * @return array<string, LocaleLink> of locale => URL
	 * @throws InvalidLinkException
	 */
	public function linksForLocaleParams(string $destination, array $localeParams): array
	{
		$linkGenerators = [$this->translator->getDefaultLocale() => $this->linkGenerator] + $this->getOtherLocaleLinkGenerators($destination);
		$links = [];
		foreach ($linkGenerators as $locale => $linkGenerator) {
			if ($this->hasLocale($localeParams, $locale)) {
				$links[$locale] = $this->createLocaleLink($locale, $linkGenerator->link($destination, $this->getParams($localeParams, $locale)));
			}
		}
		return $links;
	}


	/**
	 * Whether a page with these parameters exists in the locale, which linksForLocaleParams() would link.
	 *
	 * @param array<string, array<array-key, mixed>> $localeParams of locale => [position|name => value]
	 */
	public function hasLocale(array $localeParams, string $locale): bool
	{
		return isset($localeParams[$locale]) || isset($localeParams[self::DEFAULT_PARAMS]);
	}


	/**
	 * Return default params for all locales.
	 *
	 * @param array<array-key, mixed> $params
	 * @return array<string, array<array-key, mixed>>
	 */
	public function defaultParams(array $params): array
	{
		return [self::DEFAULT_PARAMS => $params];
	}


	/**
	 * Set default params.
	 *
	 * @param array<string, list<string>|array<string, string|null>> $params
	 * @param list<string>|array<string, string|null> $defaultParams
	 */
	public function setDefaultParams(array &$params, array $defaultParams): void
	{
		$params[self::DEFAULT_PARAMS] = $defaultParams;
	}


	/**
	 * Generates all URLs, including a link to the current language version.
	 *
	 * @param string $destination destination in format "[[[module:]presenter:]action] [#fragment]"
	 * @param array<string, list<string>|array<string, string|null>> $params of locale => [position|name => value]
	 * @return array<string, string> of locale => URL
	 * @throws InvalidLinkException
	 */
	public function allLinks(string $destination, array $params = []): array
	{
		$locale = $this->translator->getDefaultLocale();
		try {
			$links = $this->links($destination, $params);
		} catch (InvalidLinkException) {
			$links = [];
		}
		return array_merge(
			[$locale => $this->linkGenerator->link($destination, $this->getParams($params, $locale))],
			array_map(fn(LocaleLink $localeLink): string => $localeLink->getUrl(), $links),
		);
	}


	/**
	 * The locale routers hold the routes of every module served in more than one locale. With a destination, only the
	 * locales of its module, so one served in one locale has no other locales rather than links that fail to build.
	 *
	 * @return array<string, LinkGenerator> of locale => generator, for the locales other than the current one
	 */
	private function getOtherLocaleLinkGenerators(?string $destination = null): array
	{
		$linkGenerators = [];
		foreach ($this->localeRouter->getLocaleRouters() as $locale => $routers) {
			foreach ($routers->getRouters() as $router) {
				if (!$router instanceof RouteList) {
					throw new ShouldNotHappenException(sprintf("The presenter should be a '%s' but it's a %s", RouteList::class, get_debug_type($router)));
				}
				if ($destination !== null && !str_starts_with(ltrim($destination, ':'), (string)$router->getModule())) {
					continue;
				}
				if (count($router->getRouters()) > 0) {
					$linkGenerators[$locale] = new LinkGenerator($router, $this->httpRequest->getUrl(), $this->presenterFactory);
				}
			}
		}
		return $linkGenerators;
	}


	private function createLocaleLink(string $locale, string $url): LocaleLink
	{
		return new LocaleLink($locale, $this->languages[$locale]['code'], $this->languages[$locale]['name'], $url);
	}


	/**
	 * @param array<string, list<string>|array<array-key, mixed>> $params
	 * @param string $locale
	 * @return list<string>|array<array-key, mixed>
	 */
	private function getParams(array $params, string $locale): array
	{
		return $params[$locale] ?? $params[self::DEFAULT_PARAMS] ?? [];
	}

}
