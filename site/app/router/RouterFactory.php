<?php

use	Nette\Application\Routers\Route;
use Nette\Application\Routers\RouteList;


/**
 * Router factory.
 */
class RouterFactory
{

	const ADMIN = 'admin';
	const COMPANIES20 = 'firmy20';
	const WEBLEED = 'heartbleed';
	const WWW = 'www';

	const ROOT_ONLY = '';

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
		Route::addStyle('name', 'action');  // let the name param be converted like the action param (foo-bar => fooBar)
		$router = new RouteList();
		$router[] = $this->addRoute(self::ADMIN, '[<presenter>][/<action>][/<param>]', 'Homepage', 'default');
		$router[] = $this->addRoute(self::COMPANIES20, '[<param>]', 'Homepage', 'default');
		$router[] = $this->addRoute(self::WEBLEED, self::ROOT_ONLY, 'Homepage', 'default');
		$router[] = $this->addRoute(self::WWW, 'rozhovory/<name>', 'Interviews', 'rozhovor');
		$router[] = $this->addRoute(self::WWW, 'prednasky/<name>[/<slide>]', 'Talks', 'prednaska');
		$router[] = $this->addRoute(self::WWW, 'soubory[/<action>]/<filename>', 'Files', 'soubor');
		$router[] = $this->addRoute(self::WWW, 'skoleni/<name>[/<action>[/<param>]]', 'Trainings', 'skoleni');
		$router[] = $this->addRoute(self::WWW, 'r/<action>/<token>', 'R', 'default');
		$router[] = $this->addRoute(self::WWW, '<presenter>[/<action>]', 'Homepage', 'default');
		return $router;
	}


	private function addRoute($host, $mask, $defaultPresenter, $defaultAction)
	{
		$flags = 0;
		$metadata = array(
			'presenter' => array(
				Route::VALUE => $defaultPresenter,
			),
			'action' => $defaultAction,
		);
		switch ($host) {
			case self::ADMIN:
				$metadata['module'] = 'Admin';
				$flags = Route::SECURED;
				break;
			case self::COMPANIES20:
				$metadata['module'] = 'Companies20';
				$metadata['param'] = [Route::FILTER_IN => 'urldecode', Route::FILTER_OUT => 'urlencode'];
				break;
			case self::WEBLEED:
				$metadata['module'] = 'Webleed';
				break;
			case self::WWW:
				$metadata['presenter'][Route::FILTER_TABLE] = array(
					'clanky' => 'Articles',
					'kdo' => 'Who',
					'prednasky' => 'Talks',
					'rozhovory' => 'Interviews',
					'skoleni' => 'Trainings',
					'soubory' => 'Files',
				);
				break;
		}

		return new Route("//{$host}.{$this->rootDomain}/{$mask}", $metadata, $flags);
	}


}
