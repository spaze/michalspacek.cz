<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Application;

/**
 * Generates links to locales other than current.
 */
class LocaleLinkGenerator
{

	/** @var string */
	private const DEFAULT_PARAMS = '*';

	/** @var \MichalSpacekCz\Application\RouterFactory */
	private $routerFactory;

	/** @var \Nette\Http\Request */
	private $httpRequest;

	/** @var \Nette\Application\IPresenterFactory */
	private $presenterFactory;


	/**
	 * @param \MichalSpacekCz\Application\RouterFactory $routerFactory
	 * @param \Nette\Http\Request $httpRequest
	 * @param \Nette\Application\IPresenterFactory
	 */
	public function __construct(RouterFactory $routerFactory, \Nette\Http\Request $httpRequest, \Nette\Application\IPresenterFactory $presenterFactory)
	{
		$this->routerFactory = $routerFactory;
		$this->httpRequest = $httpRequest;
		$this->presenterFactory = $presenterFactory;
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
		foreach ($this->routerFactory->getLocaleRouters() as $locale => $router) {
			$linkGenerator = new \Nette\Application\LinkGenerator($router, $this->httpRequest->getUrl(), $this->presenterFactory);
			$links[$locale] = $linkGenerator->link($destination, $params[$locale] ?? $params[self::DEFAULT_PARAMS]);
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

}
