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
	 * @return Nette\Application\IRouter
	 */
	public function createRouter()
	{
		Route::addStyle('name', 'action');  // let the name param be converted like the action param (foo-bar => fooBar)
		$router = new RouteList();
		$router[] = new Route('rozhovory/<name>', 'Rozhovory:rozhovor');
		$router[] = new Route('prednasky/<name>', 'Prednasky:prednaska');
		$router[] = new Route('soubory[/<action>]/<filename>', 'Soubory:soubor');
		$router[] = new Route('skoleni/<name>[/<action>[/<param>]]', 'Skoleni:skoleni');
		$router[] = new Route('<presenter>[/<action>]', 'Homepage:default');
		return $router;
	}

}
