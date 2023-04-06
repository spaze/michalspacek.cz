<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Application;

use Contributte\Translation\Translator;
use MichalSpacekCz\Application\Routers\BlogPostRoute;
use MichalSpacekCz\Post\Loader;
use Nette\Application\Routers\Route as ApplicationRoute;
use Nette\Application\Routers\RouteList;
use Nette\Routing\Route;
use Nette\Routing\Router;

class RouterFactory
{

	/**
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

	/** @var array<string, array<string, array<string, string>>> */
	private array $translatedPresenters = [];

	/** @var array<string, array<string, array<string, array<string, string>>>> */
	private array $translatedActions = [];

	private string $currentModule;

	/** @var RouteList<Router> */
	private RouteList $currentRouteList;

	/** @var RouteList[] */
	private array $currentLocaleRouteList;

	/** @var RouteList<Router> */
	private RouteList $router;

	/** @var array<string, RouteList> */
	private array $localeRouters;

	/** @var string[] */
	private array $availableLocales;


	/**
	 * @param array<string, array<string, string>> $supportedLocales host => array of supported locales
	 * @param array<string, string> $rootDomainMapping locale => root domain
	 * @param array<string, array<string, array{mask:array<string, string>, actions?:array<string, array<string, string>>}>> $translatedRoutes
	 */
	public function __construct(
		private readonly Loader $blogPostLoader,
		private readonly Translator $translator,
		private readonly array $supportedLocales,
		private readonly array $rootDomainMapping,
		private readonly array $translatedRoutes,
	) {
		$this->availableLocales = $this->translator->getAvailableLocales();
		$this->setTranslatedPresentersAndActions();
	}


	private function setTranslatedPresentersAndActions(): void
	{
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
	 * @return RouteList<Router>
	 * @noinspection RequiredAttributes Because <param> is not an HTML tag here
	 */
	public function createRouter(): RouteList
	{
		$this->router = new RouteList();
		foreach ($this->availableLocales as $locale) {
			$this->localeRouters[$locale] = new RouteList();
		}

		$this->router->withModule('EasterEgg')->addRoute('/nette.micro', 'Nette:micro');

		$this->initRouterLists(self::MODULE_ADMIN);
		$this->addRoute('.well-known[/<action>]', 'WellKnown', 'default');
		$this->addRoute('[<presenter>][/<action>][/<param>]', 'Homepage', 'default');

		$this->initRouterLists(self::MODULE_HEARTBLEED);
		$this->addRoute(self::ROOT_ONLY, 'Homepage', 'default');

		$this->initRouterLists(self::MODULE_API);
		$this->addRoute('<presenter>[/<action>]', 'Default', 'default');

		$this->initRouterLists(self::MODULE_PULSE);
		$this->addRoute('passwords/storages[/<action>][/<param>]', 'PasswordsStorages', 'default', ['param' => [Route::Pattern => '.+']]);
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
		$this->addRoute('/<tag>', 'Tags', 'tag');
		$this->addRoute('<slug>', 'Post', 'default', null, BlogPostRoute::class);
		$this->addRoute('<presenter>', 'Homepage', 'default'); // Intentionally no action, use presenter-specific route if you need actions

		return $this->router;
	}


	/**
	 * @param string $mask
	 * @param string $defaultPresenter
	 * @param string $defaultAction
	 * @param array<string, array<string, string>>|null $initialMetadata
	 * @param class-string<ApplicationRoute> $class
	 */
	private function addRoute(string $mask, string $defaultPresenter, string $defaultAction, ?array $initialMetadata = null, string $class = ApplicationRoute::class): void
	{
		$host = self::HOSTS[$this->currentModule];
		foreach ($this->supportedLocales[$host] as $locale => $domain) {
			$metadata = $initialMetadata ?? [];
			$maskPrefix = (isset($this->translatedRoutes[$this->currentModule][$defaultPresenter]) ? $this->translatedRoutes[$this->currentModule][$defaultPresenter]['mask'][$locale] : null);
			$metadata['presenter'] = [Route::Value => $defaultPresenter];
			$metadata['action'] = [Route::Value => $defaultAction];
			if (isset($this->translatedPresenters[$this->currentModule])) {
				if ($maskPrefix === null) {
					$metadata['presenter'][Route::FilterTable] = $this->translatedPresenters[$this->currentModule][$locale];
				} else {
					$presenter = $this->translatedPresenters[$this->currentModule][$locale][$maskPrefix];
					$metadata['presenter'][Route::FilterTable] = [$maskPrefix => $presenter];
					$metadata['action'][Route::FilterTable] = $this->translatedActions[$this->currentModule][$presenter][$locale] ?? [];
				}
			}
			$hostMask = sprintf(
				'https://%s/%s%s',
				str_ends_with($domain, '.') ? rtrim($domain, '.') : "{$host}.{$this->rootDomainMapping[$domain]}",
				$maskPrefix,
				$mask,
			);
			$this->addToRouter($this->createRoute($class, $hostMask, $metadata), $locale, $host);
		}
	}


	private function addToRouter(Router $route, string $locale, string $host): void
	{
		if (count($this->supportedLocales[$host]) > 1 && $locale !== $this->translator->getLocale()) {
			$this->currentLocaleRouteList[$locale]->add($route);
		} else {
			$this->currentRouteList->add($route);
		}
	}


	/**
	 * Route factory.
	 *
	 * @param class-string<ApplicationRoute> $class
	 * @param string $mask
	 * @param array<string, array<string, array<string, string>|string>> $metadata
	 * @return Router
	 */
	private function createRoute(string $class, string $mask, array $metadata): Router
	{
		return match ($class) {
			BlogPostRoute::class => new $class($this->blogPostLoader, $mask, $metadata),
			default => new $class($mask, $metadata),
		};
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
