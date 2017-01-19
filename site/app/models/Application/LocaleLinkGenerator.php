<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Application;

/**
 * Generates links to locales other than current.
 */
class LocaleLinkGenerator
{

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
	 * @param string destination in format "[[[module:]presenter:]action] [#fragment]"
	 * @param array of name => value
	 * @return array of locale => URL
	 */
	public function links(string $destination, array $params = array()): array
	{
		$links = array();
		foreach ($this->routerFactory->getLocaleRouters() as $locale => $router) {
			$linkGenerator = new \Nette\Application\LinkGenerator($router, $this->httpRequest->getUrl(), $this->presenterFactory);
			$links[$locale] = $linkGenerator->link($destination, $params);
		}
		return $links;
	}


}
