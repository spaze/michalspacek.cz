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
	 * Hostnames.
	 * @var string
	 */
	public const HOST_ADMIN = 'admin';
	public const HOST_API = 'api';
	public const HOST_HEARTBLEED = 'heartbleed';
	public const HOST_PULSE = 'pulse';
	public const HOST_UPC = 'upc';
	public const HOST_WWW = 'www';

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

	private const ROOT_ONLY = '';

	/** @var \MichalSpacekCz\Post\Loader */
	protected $blogPostLoader;

	/** @var \Nette\Localization\ITranslator */
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

	/** @var \Nette\Application\IRouter */
	private $router;

	/** @var array of \Nette\Application\IRouter */
	private $localeRouters;


	/**
	 * @param \MichalSpacekCz\Post\Loader $blogPostLoader
	 * @param \Nette\Localization\ITranslator $translator
	 */
	public function __construct(\MichalSpacekCz\Post\Loader $blogPostLoader, \Nette\Localization\ITranslator $translator)
	{
		$this->blogPostLoader = $blogPostLoader;
		$this->translator = $translator;
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
	 * @return array of \Nette\Application\IRouter
	 */
	public function getLocaleRouters(): array
	{
		return $this->localeRouters;
	}


	/**
	 * @return \Nette\Application\IRouter
	 */
	public function createRouter(): \Nette\Application\IRouter
	{
		$this->router = new RouteList();
		$this->addRoute(self::MODULE_ADMIN, self::HOST_ADMIN, '[<presenter>][/<action>][/<param>]', 'Homepage', 'default');
		$this->addRoute(self::MODULE_HEARTBLEED, self::HOST_HEARTBLEED, self::ROOT_ONLY, 'Homepage', 'default');
		$this->addRoute(self::MODULE_API, self::HOST_API, '<presenter>', 'Default', 'default');
		$this->addRoute(self::MODULE_PULSE, self::HOST_PULSE, 'passwords/storages[/<action>][/<param>]', 'PasswordsStorages', 'default', ['param' => [Route::PATTERN => '.+']]);
		$this->addRoute(self::MODULE_PULSE, self::HOST_PULSE, '[<presenter>][/<action>][/<param>]', 'Homepage', 'default');
		$this->addRoute(self::MODULE_UPC, self::HOST_UPC, '[<ssid>][/<format>]', 'Homepage', 'default');
		$this->addRoute(self::MODULE_WWW, self::HOST_WWW, '/<name>', 'Interviews', 'interview');
		$this->addRoute(self::MODULE_WWW, self::HOST_WWW, '/<name>[/<slide>]', 'Talks', 'talk');
		$this->addRoute(self::MODULE_WWW, self::HOST_WWW, '[/<action>]/<filename>', 'Files', 'file');
		$this->addRoute(self::MODULE_WWW, self::HOST_WWW, '/<name>[/<action>[/<param>]]', 'Trainings', 'training');
		$this->addRoute(self::MODULE_WWW, self::HOST_WWW, '/<name>[/<action>]', 'CompanyTrainings', 'training');
		$this->addRoute(self::MODULE_WWW, self::HOST_WWW, '/<action>/<token>', 'Redirect', 'default');
		$this->addRoute(self::MODULE_WWW, self::HOST_WWW, '/<action>[/<param>]', 'Exports', 'default');
		$this->addRoute(self::MODULE_WWW, self::HOST_WWW, '/<name>', 'Venues', 'venue');
		$this->addRoute(self::MODULE_WWW, self::HOST_WWW, '/<tags>', 'Tags', 'tag');
		$this->addRoute(self::MODULE_WWW, self::HOST_WWW, '<slug>', 'Post', 'default', null, \MichalSpacekCz\Application\Routers\Route::class);
		$this->addRoute(self::MODULE_WWW, self::HOST_WWW, '<presenter>', 'Homepage', 'default');  // Intentionally no action, use presenter-specific route if you need actions
		return $this->router;
	}


	private function addRoute(string $module, string $host, string $mask, string $defaultPresenter, string $defaultAction, ?array $initialMetadata = null, string $class = Route::class): void
	{
		foreach ($this->supportedLocales[$host] as $locale => $tld) {
			$metadata = $initialMetadata ?? [];
			$maskPrefix = (isset($this->translatedRoutes[$module][$defaultPresenter]) ? $this->translatedRoutes[$module][$defaultPresenter]['mask'][$locale] : null);
			$metadata['presenter'] = [Route::VALUE => $defaultPresenter];
			$metadata['action'] = [Route::VALUE => $defaultAction];
			$metadata['module'] = $module;
			if (isset($this->translatedPresenters[$module])) {
				if ($maskPrefix === null) {
					$metadata['presenter'][Route::FILTER_TABLE] = $this->translatedPresenters[$module][$locale];
				} else {
					$presenter = $this->translatedPresenters[$module][$locale][$maskPrefix];
					$metadata['presenter'][Route::FILTER_TABLE] = array($maskPrefix => $presenter);
					$metadata['action'][Route::FILTER_TABLE] = (isset($this->translatedActions[$module][$presenter][$locale]) ? $this->translatedActions[$module][$presenter][$locale] : []);
				}
			}
			$this->addToRouter($this->createRoute($class, "//{$host}.{$this->rootDomainMapping[$tld]}/{$maskPrefix}{$mask}", $metadata), $locale, $host);
		}
	}


	/**
	 * @param \Nette\Application\IRouter $route
	 * @param string $locale
	 * @param string $host
	 */
	private function addToRouter(\Nette\Application\IRouter $route, string $locale, string $host): void
	{
		if (count($this->supportedLocales[$host]) > 1 && $locale !== $this->translator->getLocale()) {
			if (!isset($this->localeRouters[$locale])) {
				$this->localeRouters[$locale] = new RouteList();
			}
			$this->localeRouters[$locale][] = $route;
		} else {
			$this->router[] = $route;
		}
	}


	/**
	 * Route factory.
	 *
	 * @param string $class
	 * @param string $mask
	 * @param array $metadata
	 * @return \Nette\Application\IRouter
	 */
	private function createRoute(string $class, string $mask, array $metadata): \Nette\Application\IRouter
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

}
