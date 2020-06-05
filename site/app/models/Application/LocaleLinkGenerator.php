<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Application;

use Contributte\Translation\Translator;
use Nette\Application\IPresenterFactory;
use Nette\Application\LinkGenerator;
use Nette\Application\Routers\RouteList;
use Nette\Http\Request;
use Nette\Localization\ITranslator;

/**
 * Generates links to locales other than current.
 */
class LocaleLinkGenerator
{

	/** @var string */
	private const DEFAULT_PARAMS = '*';

	/** @var RouterFactory */
	private $routerFactory;

	/** @var Request */
	private $httpRequest;

	/** @var IPresenterFactory */
	private $presenterFactory;

	/** @var LinkGenerator */
	private $linkGenerator;

	/** @var Translator|ITranslator */
	private $translator;


	public function __construct(
		RouterFactory $routerFactory,
		Request $httpRequest,
		IPresenterFactory $presenterFactory,
		LinkGenerator $linkGenerator,
		ITranslator $translator
	) {
		$this->routerFactory = $routerFactory;
		$this->httpRequest = $httpRequest;
		$this->presenterFactory = $presenterFactory;
		$this->linkGenerator = $linkGenerator;
		$this->translator = $translator;
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
			foreach ($routers as $router) {
				/** @var RouteList $router */
				if (count($router)) {
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
	 * @param array<string, array<string, string>> $params
	 * @param array<string, array<string, string>> $defaultParams
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
			$this->links($destination, $params)
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
