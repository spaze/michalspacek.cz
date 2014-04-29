<?php

use Nette\Application\Routers\RouteList,
	Nette\Application\Routers\Route,
	Nette\Application\Routers\SimpleRouter;


/**
 * Router factory.
 */
class RouterFactory
{

	/** @var string */
	protected $rootDomain;


	public function setRootDomain($rootDomain)
	{
		$this->rootDomain = $rootDomain;
	}


	/**
	 * @return Nette\Application\IRouter
	 */
	public function createRouter()
	{
		$adminHost = "//admin.{$this->rootDomain}/";
		$companies20Host = "//firmy20.{$this->rootDomain}/";
		$webleedHost = "//heartbleed.{$this->rootDomain}/";
		$wwwHost = "//www.{$this->rootDomain}/";

		Route::addStyle('name', 'action');  // let the name param be converted like the action param (foo-bar => fooBar)
		$router = new RouteList();
		$router[] = new Route($adminHost . '[<presenter>][/<action>][/<param>]', array('module' => 'Admin', 'presenter' => 'Homepage', 'action' => 'default'), Route::SECURED);
		$router[] = new Route($companies20Host . '[<param>]', array('module' => 'Companies20', 'presenter' => 'Homepage', 'action' => 'default', 'param' => [Route::FILTER_IN => 'urldecode', Route::FILTER_OUT => 'urlencode']));
		$router[] = new Route($webleedHost, array('module' => 'Webleed', 'presenter' => 'Homepage', 'action' => 'default'));
		$router[] = new Route($wwwHost . 'rozhovory/<name>', 'Rozhovory:rozhovor');
		$router[] = new Route($wwwHost . 'prednasky/<name>[/<slide>]', 'Prednasky:prednaska');
		$router[] = new Route($wwwHost . 'soubory[/<action>]/<filename>', 'Soubory:soubor');
		$router[] = new Route($wwwHost . 'skoleni/<name>[/<action>[/<param>]]', 'Skoleni:skoleni');
		$router[] = new Route($wwwHost . 'r/<action>/<token>', 'R:default');
		$router[] = new Route($wwwHost . '<presenter>[/<action>]', 'Homepage:default');
		return $router;
	}


}
