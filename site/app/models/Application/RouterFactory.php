<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Application;

use Nette\Application\Routers\Route;
use Nette\Application\Routers\RouteList;

/**
 * Router factory.
 */
class RouterFactory
{

	/**
	 * Module names.
	 * @var string
	 */
	private const MODULE_ADMIN = 'Admin';
	private const MODULE_API = 'Api';
	private const MODULE_HEARTBLEED = 'Webleed';
	private const MODULE_PULSE = 'Pulse';
	private const MODULE_UPC = 'UpcKeys';
	private const MODULE_WWW = 'Www';

	/**
	 * Module names mapped to hostnames.
	 *
	 * @var array<string, string>
	 */
	private const HOSTS = [
		self::MODULE_ADMIN => 'admin',
		self::MODULE_API => 'api',
		self::MODULE_HEARTBLEED => 'heartbleed',
		self::MODULE_PULSE => 'pulse',
		self::MODULE_UPC => 'upc',
		self::MODULE_WWW => 'www',
	];

	private const ROOT_ONLY = '';

	/** @var \MichalSpacekCz\Post\Loader */
	protected $blogPostLoader;

	/** @var \Contributte\Translation\Translator|\Nette\Localization\ITranslator */
	protected $translator;

	/** @var array of host => array of supported locales */
	protected $supportedLocales;

	/** @var array of locale => root domain */
	protected $rootDomainMapping;

	/** @var array */
	protected $translatedRoutes;

	/** @var array */
	protected $translatedPresenters = array();

	/** @var array */
	protected $translatedActions = array();

	/** @var string */
	private $currentModule;

	/** @var RouteList */
	private $currentRouteList;

	/** @var RouteList[] */
	private $currentLocaleRouteList;

	/** @var RouteList */
	private $router;

	/** @var RouteList[] */
	private $localeRouters;

	/** @var string[] */
	private $availableLocales = [];


	/**
	 * @param \MichalSpacekCz\Post\Loader $blogPostLoader
	 * @param \Contributte\Translation\Translator|\Nette\Localization\ITranslator $translator
	 */
	public function __construct(\MichalSpacekCz\Post\Loader $blogPostLoader, \Nette\Localization\ITranslator $translator)
	{
		$this->blogPostLoader = $blogPostLoader;
		$this->translator = $translator;
		$this->availableLocales = $this->translator->getAvailableLocales();
	}


	/**
	 * Set supported locales
	 *
	 * @param array $supportedLocales array of host => array of supported locales
	 */
	public function setSupportedLocales(array $supportedLocales): void
	{
		$this->supportedLocales = $supportedLocales;
	}


	/**
	 * Set locale to root domain mapping.
	 *
	 * @param array $rootDomainMapping locale => root domain
	 */
	public function setLocaleRootDomainMapping(array $rootDomainMapping): void
	{
		$this->rootDomainMapping = $rootDomainMapping;
	}


	/**
	 * Get locale to root domain mapping.
	 *
	 * @return array $rootDomainMapping locale => root domain
	 */
	public function getLocaleRootDomainMapping(): array
	{
		return $this->rootDomainMapping;
	}


	public function setTranslatedRoutes(array $translatedRoutes): void
	{
		$this->translatedRoutes = $translatedRoutes;

		foreach ($this->translatedRoutes as $module => $routes) {
			foreach ($routes as $presenter => $items) {
				foreach ($items['mask'] as $locale => $mask) {
					$this->translatedPresenters[$module][$locale][$mask] = $presenter;
				}
				if (isset($items['actions'])) {
					foreach ($items['actions'] as $action => $actions) {
						foreach ($actions as $locale => $translated) {
							$this->translatedActions[$module][$presenter][$locale][$translated] = $action;
						}
					}
				}
			}
		}
	}


	/**
	 * Get locale routers.
	 *
	 * @return array<string, RouteList>
	 */
	public function getLocaleRouters(): array
	{
		return $this->localeRouters;
	}


	/**
	 * @return RouteList
	 */
	public function createRouter(): RouteList
	{
		$this->router = new RouteList();
		foreach ($this->availableLocales as $locale) {
			$this->localeRouters[$locale] = new RouteList();
		}

		$this->initRouterLists(self::MODULE_ADMIN);
		$this->addRoute('[<presenter>][/<action>][/<param>]', 'Homepage', 'default');

		$this->initRouterLists(self::MODULE_HEARTBLEED);
		$this->addRoute(self::ROOT_ONLY, 'Homepage', 'default');

		$this->initRouterLists(self::MODULE_API);
		$this->addRoute('<presenter>', 'Default', 'default');

		$this->initRouterLists(self::MODULE_PULSE);
		$this->addRoute('passwords/storages[/<action>][/<param>]', 'PasswordsStorages', 'default', ['param' => [Route::PATTERN => '.+']]);
		$this->addRoute('[<presenter>][/<action>][/<param>]', 'Homepage', 'default');

		$this->initRouterLists(self::MODULE_UPC);
		$this->addRoute('[<ssid>][/<format>]', 'Homepage', 'default');

		$this->initRouterLists(self::MODULE_WWW);
		$this->addRoute('/<name>', 'Interviews', 'interview');
		$this->addRoute('/<name>[/<slide>]', 'Talks', 'talk');
		$this->addRoute('[/<action>]/<filename>', 'Files', 'file');
		$this->addRoute('/<name>[/<action>[/<param>]]', 'Trainings', 'training');
		$this->addRoute('/<name>[/<action>]', 'CompanyTrainings', 'training');
		$this->addRoute('/<action>/<token>', 'Redirect', 'default');
		$this->addRoute('/<action>[/<param>]', 'Exports', 'default');
		$this->addRoute('/<name>', 'Venues', 'venue');
		$this->addRoute('/<tags>', 'Tags', 'tag');
		$this->addRoute('<slug>', 'Post', 'default', null, \MichalSpacekCz\Application\Routers\Route::class);
		$this->addRoute('<presenter>', 'Homepage', 'default');  // Intentionally no action, use presenter-specific route if you need actions

		return $this->router;
	}


	private function addRoute(string $mask, string $defaultPresenter, string $defaultAction, ?array $initialMetadata = null, string $class = Route::class): void
	{
		$host = self::HOSTS[$this->currentModule];
		foreach ($this->supportedLocales[$host] as $locale => $tld) {
			$metadata = $initialMetadata ?? [];
			$maskPrefix = (isset($this->translatedRoutes[$this->currentModule][$defaultPresenter]) ? $this->translatedRoutes[$this->currentModule][$defaultPresenter]['mask'][$locale] : null);
			$metadata['presenter'] = [Route::VALUE => $defaultPresenter];
			$metadata['action'] = [Route::VALUE => $defaultAction];
			if (isset($this->translatedPresenters[$this->currentModule])) {
				if ($maskPrefix === null) {
					$metadata['presenter'][Route::FILTER_TABLE] = $this->translatedPresenters[$this->currentModule][$locale];
				} else {
					$presenter = $this->translatedPresenters[$this->currentModule][$locale][$maskPrefix];
					$metadata['presenter'][Route::FILTER_TABLE] = array($maskPrefix => $presenter);
					$metadata['action'][Route::FILTER_TABLE] = (isset($this->translatedActions[$this->currentModule][$presenter][$locale]) ? $this->translatedActions[$this->currentModule][$presenter][$locale] : []);
				}
			}
			$this->addToRouter($this->currentModule, $this->createRoute($class, "//{$host}.{$this->rootDomainMapping[$tld]}/{$maskPrefix}{$mask}", $metadata), $locale, $host);
		}
	}


	private function addToRouter(string $module, \Nette\Routing\Router $route, string $locale, string $host): void
	{
		if (count($this->supportedLocales[$host]) > 1 && $locale !== $this->translator->getLocale()) {
			$this->currentLocaleRouteList[$locale][] = $route;
		} else {
			$this->currentRouteList[] = $route;
		}
	}


	/**
	 * Route factory.
	 *
	 * @param string $class
	 * @param string $mask
	 * @param array $metadata
	 * @return \Nette\Routing\Router
	 */
	private function createRoute(string $class, string $mask, array $metadata): \Nette\Routing\Router
	{
		switch ($class) {
			case \MichalSpacekCz\Application\Routers\Route::class:
				$route = new $class($this->blogPostLoader, $mask, $metadata);
				break;
			default:
				$route = new $class($mask, $metadata);
				break;
		}
		return $route;
	}


	private function initRouterLists(string $module): void
	{
		$this->currentModule = $module;
		$this->currentRouteList = $this->router->withModule($module);
		foreach ($this->availableLocales as $locale) {
			$this->currentLocaleRouteList[$locale] = $this->localeRouters[$locale]->withModule($module);
		}
	}

}
