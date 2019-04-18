<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Application;

use Contributte\Translation\Translator;
use Nette\Application\IPresenterFactory;
use Nette\Application\LinkGenerator;
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
	 * @param array $params of locale => [name => value]
	 * @return array of locale => URL
	 */
	public function links(string $destination, array $params = array()): array
	{
		$links = array();
		foreach ($this->routerFactory->getLocaleRouters() as $locale => $routers) {
			foreach ($routers as $router) {
				if (count($router)) {
					$linkGenerator = new LinkGenerator($router, $this->httpRequest->getUrl(), $this->presenterFactory);
					$links[$locale] = $linkGenerator->link($destination, $params[$locale] ?? $params[self::DEFAULT_PARAMS] ?? []);
				}
			}
		}
		return $links;
	}


	/**
	 * Return default params for all locales.
	 *
	 * @param array $params
	 * @return array
	 */
	public function defaultParams(array $params): array
	{
		return [self::DEFAULT_PARAMS => $params];
	}


	/**
	 * Generates all URLs, including a link to the current language version.
	 *
	 * @param string $destination destination in format "[[[module:]presenter:]action] [#fragment]"
	 * @param array $params of locale => [name => value]
	 * @return array of locale => URL
	 */
	public function allLinks(string $destination, array $params = []): array
	{
		return array_merge(
			[$this->translator->getDefaultLocale() => $this->linkGenerator->link($destination, $params)],
			$this->links($destination, $params)
		);
	}

}
