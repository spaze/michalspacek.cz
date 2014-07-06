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

	/** @var array */
	protected $translatedPresenters;


	public function setRootDomain($rootDomain)
	{
		$this->rootDomain = $rootDomain;
	}


	public function setTranslatedPresenters(array $translatedPresenters)
	{
		$this->translatedPresenters = $translatedPresenters;
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
		$router[] = $this->addRoute(self::WWW, '/<name>', 'Interviews', 'rozhovor');
		$router[] = $this->addRoute(self::WWW, '/<name>[/<slide>]', 'Talks', 'prednaska');
		$router[] = $this->addRoute(self::WWW, '[/<action>]/<filename>', 'Files', 'soubor');
		$router[] = $this->addRoute(self::WWW, '/<name>[/<action>[/<param>]]', 'Trainings', 'skoleni');
		$router[] = $this->addRoute(self::WWW, '/<action>/<token>', 'R', 'default');
		$router[] = $this->addRoute(self::WWW, '<presenter>[/<action>]', 'Homepage', 'default');
		return $router;
	}


	private function addRoute($host, $mask, $defaultPresenter, $defaultAction)
	{
		$maskPrefix = (isset($this->translatedPresenters[$host][$defaultPresenter]) ? $this->translatedPresenters[$host][$defaultPresenter]['mask'] : null);
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
				$table = array();
				foreach ($this->translatedPresenters[$host] as $presenter => $items) {
					$table[$items['mask']] = $presenter;
				}
				$metadata['presenter'][Route::FILTER_TABLE] = $table;
				break;
		}

		return new Route("//{$host}.{$this->rootDomain}/{$maskPrefix}{$mask}", $metadata, $flags);
	}


}
