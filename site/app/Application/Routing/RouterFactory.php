<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Application\Routing;

use Contributte\Translation\Translator;
use MichalSpacekCz\Articles\Blog\BlogPostLoader;
use Nette\Application\Routers\Route as ApplicationRoute;
use Nette\Application\Routers\RouteList;
use Nette\Routing\Route;
use Nette\Routing\Router;

class RouterFactory
{

	private const MODULE_ADMIN = 'Admin';
	private const MODULE_API = 'Api';
	private const MODULE_HEARTBLEED = 'Webleed';
	private const MODULE_PULSE = 'Pulse';
	private const MODULE_UPC = 'UpcKeys';
	private const MODULE_WWW = 'Www';

	/**
	 * Module names mapped to hostnames.
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

	/** @var array<string, RouteList> */
	private array $currentLocaleRouteList = [];

	/** @var array<string, RouteList> */
	private array $localeRouters = [];

	/** @var string[] */
	private array $availableLocales;


	/**
	 * @param array<string, array<string, string>> $supportedLocales host => array of supported locales
	 * @param array<string, string> $rootDomainMapping locale => root domain
	 * @param array<string, array<string, array{mask:array<string, string>, actions?:array<string, array<string, string>>}>> $translatedRoutes
	 */
	public function __construct(
		private readonly BlogPostLoader $blogPostLoader,
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
	 * @noinspection RequiredAttributes Because <param> is not an HTML tag here
	 */
	public function createRouter(): RouteList
	{
		$router = new RouteList();
		foreach ($this->availableLocales as $locale) {
			$this->localeRouters[$locale] = new RouteList();
		}

		$router->withModule('EasterEgg')->addRoute('/nette.micro', 'Nette:micro');

		$this->initRouterLists($router, self::MODULE_ADMIN, [
			new RouterFactoryRoute('.well-known[/<action>]', 'WellKnown', 'default'),
			new RouterFactoryRoute('[<presenter>][/<action>][/<param>]', 'Homepage', 'default'),
		]);

		$this->initRouterLists($router, self::MODULE_HEARTBLEED, [
			new RouterFactoryRoute(self::ROOT_ONLY, 'Homepage', 'default'),
		]);

		$this->initRouterLists($router, self::MODULE_API, [
			new RouterFactoryRoute('<presenter>[/<action>]', 'Default', 'default'),
		]);

		$this->initRouterLists($router, self::MODULE_PULSE, [
			new RouterFactoryRoute('passwords/storages[/<action>][/<param>]', 'PasswordsStorages', 'default', ['param' => [Route::Pattern => '.+']]),
			new RouterFactoryRoute('[<presenter>][/<action>][/<param>]', 'Homepage', 'default'),
		]);

		$this->initRouterLists($router, self::MODULE_UPC, [
			new RouterFactoryRoute('[<ssid>][/<format>]', 'Homepage', 'default'),
		]);

		$this->initRouterLists($router, self::MODULE_WWW, [
			new RouterFactoryRoute('/<name>', 'Interviews', 'interview'),
			new RouterFactoryRoute('/<name>[/<slide>]', 'Talks', 'talk'),
			new RouterFactoryRoute('[/<action>]/<filename>', 'Files', 'file'),
			new RouterFactoryRoute('/<name>[/<action>[/<param>]]', 'Trainings', 'training'),
			new RouterFactoryRoute('/<name>[/<action>]', 'CompanyTrainings', 'training'),
			new RouterFactoryRoute('/<action>/<token>', 'Redirect', 'default'),
			new RouterFactoryRoute('/<action>[/<param>]', 'Exports', 'default'),
			new RouterFactoryRoute('/<name>', 'Venues', 'venue'),
			new RouterFactoryRoute('/<tag>', 'Tags', 'tag'),
			new RouterFactoryRoute('<slug>', 'Post', 'default', null, RouterRoutes::BlogPostRoute),
			new RouterFactoryRoute('<presenter>', 'Homepage', 'default'), // Intentionally no action, use presenter-specific route if you need actions
		]);

		return $router;
	}


	private function addRoute(string $currentModule, RouteList $currentRouteList, RouterFactoryRoute $route): void
	{
		$host = self::HOSTS[$currentModule];
		foreach ($this->supportedLocales[$host] as $locale => $domain) {
			$metadata = $route->initialMetadata ?? [];
			$maskPrefix = (isset($this->translatedRoutes[$currentModule][$route->defaultPresenter]) ? $this->translatedRoutes[$currentModule][$route->defaultPresenter]['mask'][$locale] : null);
			$metadata['presenter'] = [Route::Value => $route->defaultPresenter];
			$metadata['action'] = [Route::Value => $route->defaultAction];
			if (isset($this->translatedPresenters[$currentModule])) {
				if ($maskPrefix === null) {
					$metadata['presenter'][Route::FilterTable] = $this->translatedPresenters[$currentModule][$locale];
				} else {
					$presenter = $this->translatedPresenters[$currentModule][$locale][$maskPrefix];
					$metadata['presenter'][Route::FilterTable] = [$maskPrefix => $presenter];
					$metadata['action'][Route::FilterTable] = $this->translatedActions[$currentModule][$presenter][$locale] ?? [];
				}
			}
			$hostMask = sprintf(
				'https://%s/%s%s',
				str_ends_with($domain, '.') ? rtrim($domain, '.') : "{$host}.{$this->rootDomainMapping[$domain]}",
				$maskPrefix ?? '',
				$route->mask,
			);
			if (count($this->supportedLocales[$host]) > 1 && $locale !== $this->translator->getLocale()) {
				$this->currentLocaleRouteList[$locale]->add($this->createRoute($route->class, $hostMask, $metadata));
			} else {
				$currentRouteList->add($this->createRoute($route->class, $hostMask, $metadata));
			}
		}
	}


	/**
	 * @param array<string, array<string, array<string, string>|string>> $metadata
	 */
	private function createRoute(RouterRoutes $class, string $mask, array $metadata): Router
	{
		return match ($class) {
			RouterRoutes::BlogPostRoute => new BlogPostRoute($this->blogPostLoader, $mask, $metadata),
			RouterRoutes::Route => new ApplicationRoute($mask, $metadata),
		};
	}


	/**
	 * @param list<RouterFactoryRoute> $routes
	 */
	private function initRouterLists(RouteList $router, string $module, array $routes): void
	{
		foreach ($this->availableLocales as $locale) {
			$this->currentLocaleRouteList[$locale] = $this->localeRouters[$locale]->withModule($module);
		}
		$currentRouteList = $router->withModule($module);
		foreach ($routes as $route) {
			$this->addRoute($module, $currentRouteList, $route);
		}
	}

}
