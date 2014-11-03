<?php

use	Nette\Application\Routers\Route;
use Nette\Application\Routers\RouteList;


/**
 * Router factory.
 */
class RouterFactory
{

	const ADMIN = 'admin';
	const COTEL = 'cotel';
	const WEBLEED = 'webleed';
	const HEARTBLEED = 'heartbleed';
	const WWW = 'www';

	const ROOT_ONLY = '';

	/** @var string */
	protected $rootDomain;

	/** @var array */
	protected $translatedRoutes;

	/** @var array */
	protected $translatedPresenters = array();

	/** @var array */
	protected $translatedActions = array();


	public function setRootDomain($rootDomain)
	{
		$this->rootDomain = $rootDomain;
	}


	public function setTranslatedRoutes(array $translatedRoutes)
	{
		$this->translatedRoutes = $translatedRoutes;

		foreach ($this->translatedRoutes as $host => $routes) {
			foreach ($routes as $presenter => $items) {
				$this->translatedPresenters[$host][$items['mask']] = $presenter;
				$this->translatedActions[$host][$presenter] = array();
				if (isset($items['actions'])) {
					foreach ($items['actions'] as $action => $translated) {
						$this->translatedActions[$host][$presenter][$translated] = $action;
					}
				}
			}
		}
	}


	/**
	 * @return Nette\Application\IRouter
	 */
	public function createRouter()
	{
		Route::addStyle('name', 'action');  // let the name param be converted like the action param (foo-bar => fooBar)
		$router = new RouteList();
		$router[] = $this->addRoute(self::ADMIN, '[<presenter>][/<action>][/<param>]', 'Homepage', 'default');
		$router[] = $this->addRoute(self::COTEL, '[<param>]', 'Homepage', 'default');
		$router[] = $this->addRoute(self::WEBLEED, self::ROOT_ONLY, 'Homepage', 'default');
		$router[] = $this->addRoute(self::HEARTBLEED, self::ROOT_ONLY, 'Homepage', 'default');
		$router[] = $this->addRoute(self::WWW, '/<name>', 'Interviews', 'interview');
		$router[] = $this->addRoute(self::WWW, '/<name>[/<slide>]', 'Talks', 'talk');
		$router[] = $this->addRoute(self::WWW, '[/<action>]/<filename>', 'Files', 'file');
		$router[] = $this->addRoute(self::WWW, '/<name>[/<action>[/<param>]]', 'Trainings', 'training');
		$router[] = $this->addRoute(self::WWW, '/<name>[/<action>]', 'CompanyTrainings', 'training');
		$router[] = $this->addRoute(self::WWW, '/<action>/<token>', 'Redirect', 'default');
		$router[] = $this->addRoute(self::WWW, 'report[/<action>]', 'Report', 'default');
		$router[] = $this->addRoute(self::WWW, '<presenter>', 'Homepage', 'default');  // Intentionally no action, use presenter-specific route if you need actions
		return $router;
	}


	private function addRoute($host, $mask, $defaultPresenter, $defaultAction)
	{
		$maskPrefix = (isset($this->translatedRoutes[$host][$defaultPresenter]) ? $this->translatedRoutes[$host][$defaultPresenter]['mask'] : null);
		$flags = Route::SECURED;
		$metadata = array(
			'presenter' => [Route::VALUE => $defaultPresenter],
			'action' => [Route::VALUE => $defaultAction],
		);
		switch ($host) {
			case self::ADMIN:
				$metadata['module'] = 'Admin';
				break;
			case self::COTEL:
				$metadata['module'] = 'Companies20';
				$metadata['param'] = [Route::FILTER_IN => 'urldecode', Route::FILTER_OUT => 'urlencode'];
				break;
			case self::WEBLEED:
				$flags = Route::ONE_WAY;
				// no break;
			case self::HEARTBLEED:
				$metadata['module'] = 'Webleed';
				break;
			case self::WWW:
				if ($maskPrefix === null) {
					$metadata['presenter'][Route::FILTER_TABLE] = $this->translatedPresenters[$host];
				} else {
					$presenter = $this->translatedPresenters[$host][$maskPrefix];
					$metadata['presenter'][Route::FILTER_TABLE] = array($maskPrefix => $presenter);
					$metadata['action'][Route::FILTER_TABLE] = $this->translatedActions[$host][$presenter];
				}
				break;
		}

		return new Route("//{$host}.{$this->rootDomain}/{$maskPrefix}{$mask}", $metadata, $flags);
	}


}
