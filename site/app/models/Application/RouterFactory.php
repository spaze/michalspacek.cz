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

	const TRANSLATE_THIS = '-TranslateThis';

	/**
	 * Modules, the value sometimes matches a hostname
	 * @var string
	 */
	const ADMIN = 'admin';
	const API = 'api';
	const BLOG = 'blog';
	const HEARTBLEED = 'heartbleed';
	const PULSE = 'pulse';
	const UPC = 'upc';
	const WWW = 'www';

	const ROOT_ONLY = '';

	/** @var \MichalSpacekCz\Blog\PostLoader */
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
	 * @param \MichalSpacekCz\Blog\Post $blogPostLoader
	 * @param \Nette\Localization\ITranslator $translator
	 */
	public function __construct(\MichalSpacekCz\Blog\PostLoader $blogPostLoader, \Nette\Localization\ITranslator $translator)
	{
		$this->blogPostLoader = $blogPostLoader;
		$this->translator = $translator;
	}


	/**
	 * Set supported locales
	 *
	 * @param array $supportedLocales array of host => array of supported locales
	 */
	public function setSupportedLocales($supportedLocales): void
	{
		$this->supportedLocales = $supportedLocales;
	}


	/**
	 * Set locale to root domain mapping.
	 *
	 * @param array $rootDomainMapping locale => root domain
	 */
	public function setLocaleRootDomainMapping($rootDomainMapping): void
	{
		$this->rootDomainMapping = $rootDomainMapping;
	}


	/**
	 * Get locale to root domain mapping.
	 *
	 * @return array $rootDomainMapping locale => root domain
	 */
	public function getLocaleRootDomainMapping()
	{
		return $this->rootDomainMapping;
	}


	public function setTranslatedRoutes(array $translatedRoutes): void
	{
		$this->translatedRoutes = $translatedRoutes;

		foreach ($this->translatedRoutes as $host => $routes) {
			foreach ($routes as $presenter => $items) {
				foreach ($items['mask'] as $locale => $mask) {
					$this->translatedPresenters[$host][$locale][$mask] = $presenter;
				}
				if (isset($items['actions'])) {
					foreach ($items['actions'] as $action => $actions) {
						foreach ($actions as $locale => $translated) {
							$this->translatedActions[$host][$presenter][$locale][$translated] = $action;
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
	public function getLocaleRouters()
	{
		return $this->localeRouters;
	}


	/**
	 * @return \Nette\Application\IRouter
	 */
	public function createRouter()
	{
		$this->router = new RouteList();
		$this->addRoute(self::ADMIN, '[<presenter>][/<action>][/<param>]', 'Homepage', 'default');
		$this->addRoute(self::HEARTBLEED, self::ROOT_ONLY, 'Homepage', 'default');
		$this->addRoute(self::API, '<presenter>', 'Default', 'default');
		$this->addRoute(self::PULSE, 'passwords/storages[/<action>][/<param>]', 'PasswordsStorages', 'default');
		$this->addRoute(self::PULSE, '[<presenter>][/<action>][/<param>]', 'Homepage', 'default');
		$this->addRoute(self::UPC, '[<ssid>][/<format>]', 'Homepage', 'default');
		$this->addRoute(self::WWW, '/<name>', 'Interviews', 'interview');
		$this->addRoute(self::WWW, '/<name>[/<slide>]', 'Talks', 'talk');
		$this->addRoute(self::WWW, '[/<action>]/<filename>', 'Files', 'file');
		$this->addRoute(self::WWW, '/<name>[/<action>[/<param>]]', 'Trainings', 'training');
		$this->addRoute(self::WWW, '/<name>[/<action>]', 'CompanyTrainings', 'training');
		$this->addRoute(self::WWW, '/<action>/<token>', 'Redirect', 'default');
		$this->addRoute(self::WWW, 'report[/<action>]', 'Report', 'default');
		$this->addRoute(self::WWW, '/<name>', 'Venues', 'venue');
		$this->addRoute(self::BLOG, '<post>', 'Post', 'default', self::WWW, \MichalSpacekCz\Blog\Application\Routers\Route::class);
		$this->addRoute(self::WWW, '<presenter>', 'Homepage', 'default');  // Intentionally no action, use presenter-specific route if you need actions
		return $this->router;
	}


	private function addRoute($module, $mask, $defaultPresenter, $defaultAction, $host = null, $class = Route::class): void
	{
		$host = $host ?? $module;
		foreach ($this->supportedLocales[$host] as $locale => $tld) {
			$maskPrefix = (isset($this->translatedRoutes[$module][$defaultPresenter]) ? $this->translatedRoutes[$module][$defaultPresenter]['mask'][$locale] : null);
			$metadata = array(
				'presenter' => [Route::VALUE => $defaultPresenter],
				'action' => [Route::VALUE => $defaultAction],
			);
			switch ($module) {
				case self::API:
					$metadata['module'] = 'Api';
					break;
				case self::ADMIN:
					$metadata['module'] = 'Admin';
					break;
				case self::HEARTBLEED:
					$metadata['module'] = 'Webleed';
					break;
				case self::UPC:
					$metadata['module'] = 'UpcKeys';
					break;
				case self::PULSE:
					$metadata['module'] = 'Pulse';
					break;
				case self::BLOG:
					$metadata['module'] = 'Blog';
					// Intentionally no break, blog lives on www host
				case self::WWW:
					if ($maskPrefix === null) {
						$metadata['presenter'][Route::FILTER_TABLE] = $this->translatedPresenters[$module][$locale];
					} else {
						$presenter = $this->translatedPresenters[$module][$locale][$maskPrefix];
						$metadata['presenter'][Route::FILTER_TABLE] = array($maskPrefix => $presenter);
						$metadata['action'][Route::FILTER_TABLE] = (isset($this->translatedActions[$module][$presenter][$locale]) ? $this->translatedActions[$module][$presenter][$locale] : []);
					}
					break;
			}
			$this->addToRouter($this->createRoute($class, "//{$host}.{$this->rootDomainMapping[$tld]}/{$maskPrefix}{$mask}", $metadata), $locale, $host);
		}
	}


	/**
	 * @param \Nette\Application\IRouter $route
	 * @param string $locale
	 * @param string $host
	 */
	private function addToRouter(\Nette\Application\IRouter $route, $locale, $host): void
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
			case \MichalSpacekCz\Blog\Application\Routers\Route::class:
				$route = new $class($this->blogPostLoader, $mask, $metadata);
				break;
			default:
				$route = new $class($mask, $metadata);
				break;
		}
		return $route;
	}

}
