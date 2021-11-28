<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Application;

use Contributte\Translation\Translator;
use Nette\Application\IPresenterFactory;
use Nette\Application\LinkGenerator;
use Nette\Application\Routers\RouteList;
use Nette\Http\IRequest;

/**
 * Generates links to locales other than current.
 */
class LocaleLinkGenerator
{

	/** @var string */
	private const DEFAULT_PARAMS = '*';


	public function __construct(
		private RouterFactory $routerFactory,
		private IRequest $httpRequest,
		private IPresenterFactory $presenterFactory,
		private LinkGenerator $linkGenerator,
		private Translator $translator,
	) {
	}


	/**
	 * Generates localized URLs.
	 *
	 * @param string $destination destination in format "[[[module:]presenter:]action] [#fragment]"
	 * @param array<string, array<string, string>> $params of locale => [name => value]
	 * @return array<string, string> of locale => URL
	 */
	public function links(string $destination, array $params = array()): array
	{
		$links = array();
		foreach ($this->routerFactory->getLocaleRouters() as $locale => $routers) {
			foreach ($routers->getRouters() as $router) {
				/** @var RouteList $router */
				if (count($router->getRouters())) {
					$linkGenerator = new LinkGenerator($router, $this->httpRequest->getUrl(), $this->presenterFactory);
					$links[$locale] = $linkGenerator->link($destination, $this->getParams($params, $locale));
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
	 * @param array<string, array<string, string|null>> $params
	 * @param array<string, string|null> $defaultParams
	 */
	public function setDefaultParams(array &$params, array $defaultParams): void
	{
		$params[self::DEFAULT_PARAMS] = $defaultParams;
	}


	/**
	 * Generates all URLs, including a link to the current language version.
	 *
	 * @param string $destination destination in format "[[[module:]presenter:]action] [#fragment]"
	 * @param array<string, array<string, string>> $params of locale => [name => value]
	 * @return array<string, string> of locale => URL
	 */
	public function allLinks(string $destination, array $params = []): array
	{
		$locale = (string)$this->translator->getDefaultLocale();
		return array_merge(
			[$locale => $this->linkGenerator->link($destination, $this->getParams($params, $locale))],
			$this->links($destination, $params),
		);
	}


	/**
	 * @param array<string, array<string, string>> $params
	 * @param string $locale
	 * @return array<string, string>
	 */
	private function getParams(array $params, string $locale): array
	{
		return $params[$locale] ?? $params[self::DEFAULT_PARAMS] ?? [];
	}

}
