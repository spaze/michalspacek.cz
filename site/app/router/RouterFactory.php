<?php

use Nette\Application\Routers\RouteList,
	Nette\Application\Routers\Route,
	Nette\Application\Routers\SimpleRouter;


/**
 * Router factory.
 */
class RouterFactory
{

	/**
	 * Pattern for matching example.com in www.example.com.
	 *
	 * @var string
	 */
	const ROOT_DOMAIN_PATTERN = '/([^.]+\.[^.]+)$/';

	/** @var \Nette\Http\IRequest */
	protected $httpRequest;


	public function __construct(\Nette\Http\IRequest $httpRequest)
	{
		$this->httpRequest = $httpRequest;
	}


	/**
	 * @return Nette\Application\IRouter
	 */
	public function createRouter()
	{
		$rootDomain = $this->getRootDomain();
		$adminHost = "//admin.$rootDomain/";
		$wwwHost = "//www.$rootDomain/";

		Route::addStyle('name', 'action');  // let the name param be converted like the action param (foo-bar => fooBar)
		$router = new RouteList();
		$router[] = new Route($adminHost . '[<presenter>][/<action>][/<param>]', array('module' => 'Admin', 'presenter' => 'Homepage', 'action' => 'default'), Route::SECURED);
		$router[] = new Route($wwwHost . 'rozhovory/<name>', 'Rozhovory:rozhovor');
		$router[] = new Route($wwwHost . 'prednasky/<name>[/<slide>]', 'Prednasky:prednaska');
		$router[] = new Route($wwwHost . 'soubory[/<action>]/<filename>', 'Soubory:soubor');
		$router[] = new Route($wwwHost . 'skoleni/<name>[/<action>[/<param>]]', 'Skoleni:skoleni');
		$router[] = new Route($wwwHost . 'r/<action>/<token>', 'R:default');
		$router[] = new Route($wwwHost . '<presenter>[/<action>]', 'Homepage:default');
		return $router;
	}


	protected function getRootDomain()
	{
		$host = $this->httpRequest->getUrl()->getHost();
		$matches = array();
		if (preg_match(self::ROOT_DOMAIN_PATTERN, $host, $matches)) {
			$host = $matches[1];
		}
		return $host;
	}


}
