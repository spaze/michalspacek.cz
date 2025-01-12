<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Application\Locale;

use Contributte\Translation\Translator;
use MichalSpacekCz\Application\LinkGenerator;
use MichalSpacekCz\Application\Routing\LocaleRouter;
use MichalSpacekCz\ShouldNotHappenException;
use Nette\Application\IPresenterFactory;
use Nette\Application\LinkGenerator as NetteLinkGenerator;
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
	 * @param array<string, list<string>|array<string, string|null>> $params of locale => [position|name => value]
	 * @return array<string, LocaleLink> of locale => URL
	 * @throws InvalidLinkException
	 */
	public function links(string $destination, array $params = []): array
	{
		$links = [];
		foreach ($this->localeRouter->getLocaleRouters() as $locale => $routers) {
			foreach ($routers->getRouters() as $router) {
				if (!$router instanceof RouteList) {
					throw new ShouldNotHappenException(sprintf("The presenter should be a '%s' but it's a %s", RouteList::class, get_debug_type($router)));
				}
				if (count($router->getRouters())) {
					$linkGenerator = new NetteLinkGenerator($router, $this->httpRequest->getUrl(), $this->presenterFactory);
					$links[$locale] = new LocaleLink(
						$locale,
						$this->languages[$locale]['code'],
						$this->languages[$locale]['name'],
						$this->linkGenerator->link($destination, $this->getParams($params, $locale), $linkGenerator),
					);
				}
			}
		}
		return $links;
	}


	/**
	 * Return default params for all locales.
	 *
	 * @param array<string, string|null> $params
	 * @return array<string, array<string, string|null>>
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
	 * @param array<string, list<string>|array<string, string|null>> $params
	 * @param string $locale
	 * @return list<string>|array<string, string|null>
	 */
	private function getParams(array $params, string $locale): array
	{
		return $params[$locale] ?? $params[self::DEFAULT_PARAMS] ?? [];
	}

}
