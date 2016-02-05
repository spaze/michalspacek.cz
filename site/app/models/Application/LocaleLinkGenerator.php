<?php
namespace MichalSpacekCz\Application;

class LocaleLinkGenerator
{

	/** @var \MichalSpacekCz\Application\RouterFactory */
	protected $routerFactory;

	/** @var \Nette\Http\Request */
	protected $httpRequest;


	/**
	 * @param \MichalSpacekCz\Application\RouterFactory $routerFactory
	 * @param \Nette\Http\Request $httpRequest
	 */
	public function __construct(RouterFactory $routerFactory, \Nette\Http\Request $httpRequest)
	{
		$this->routerFactory = $routerFactory;
		$this->httpRequest = $httpRequest;
	}


	/**
	 * Generates localized URLs.
	 *
	 * @param string destination in format "[[[module:]presenter:]action] [#fragment]"
	 * @param array of name => value
	 * @return array of locale => URL
	 */
	public function links($destination, array $params = array())
	{
		$links = array();
		foreach ($this->routerFactory->getLocaleRouters() as $locale => $router) {
			$linkGenerator = new \Nette\Application\LinkGenerator($router, $this->httpRequest->getUrl());
			$links[$locale] = $linkGenerator->link($destination, $params);
		}
		return $links;
	}


}
