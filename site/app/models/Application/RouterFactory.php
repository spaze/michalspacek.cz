<?php
namespace MichalSpacekCz\Application;

use Nette\Application\Routers\Route;
use Nette\Application\Routers\RouteList;

/**
 * Router factory.
 */
class RouterFactory
{

	const TRANSLATE_THIS = '-TranslateThis';

	const ADMIN = 'admin';
	const API = 'api';
	const WEBLEED = 'webleed';
	const HEARTBLEED = 'heartbleed';
	const UPC = 'upc';
	const WWW = 'www';

	const ROOT_ONLY = '';

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


	/**
	 * @param \Nette\Localization\ITranslator $translator
	 */
	public function __construct(\Nette\Localization\ITranslator $translator)
	{
		$this->translator = $translator;
	}


	/**
	 * Set supported locales
	 *
	 * @param array $supportedLocales array of host => array of supported locales
	 */
	public function setSupportedLocales($supportedLocales)
	{
		$this->supportedLocales = $supportedLocales;
	}


	/**
	 * Set locale to root domain mapping
	 *
	 * @param array $rootDomainMapping locale => root domain
	 */
	public function setLocaleRootDomainMapping($rootDomainMapping)
	{
		$this->rootDomainMapping = $rootDomainMapping;
	}


	public function setTranslatedRoutes(array $translatedRoutes)
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
	 * @return \Nette\Application\IRouter
	 */
	public function createRouter()
	{
		$this->router = new RouteList();
		$this->addRoute(self::ADMIN, '[<presenter>][/<action>][/<param>]', 'Homepage', 'default');
		$this->addRoute(self::WEBLEED, self::ROOT_ONLY, 'Homepage', 'default');
		$this->addRoute(self::HEARTBLEED, self::ROOT_ONLY, 'Homepage', 'default');
		$this->addRoute(self::API, '/<presenter>', 'Default', 'default');
		$this->addRoute(self::UPC, '[<ssid>]', 'Homepage', 'default');
		$this->addRoute(self::WWW, '/<name>', 'Interviews', 'interview');
		$this->addRoute(self::WWW, '/<name>[/<slide>]', 'Talks', 'talk');
		$this->addRoute(self::WWW, '[/<action>]/<filename>', 'Files', 'file');
		$this->addRoute(self::WWW, '/<name>[/<action>[/<param>]]', 'Trainings', 'training');
		$this->addRoute(self::WWW, '/<name>[/<action>]', 'CompanyTrainings', 'training');
		$this->addRoute(self::WWW, '/<action>/<token>', 'Redirect', 'default');
		$this->addRoute(self::WWW, 'report[/<action>]', 'Report', 'default');
		$this->addRoute(self::WWW, '/<name>', 'Venues', 'venue');
		$this->addRoute(self::WWW, '<presenter>', 'Homepage', 'default');  // Intentionally no action, use presenter-specific route if you need actions
		return $this->router;
	}


	private function addRoute($module, $mask, $defaultPresenter, $defaultAction)
	{
		foreach ($this->supportedLocales[$module] as $locale) {
			$maskPrefix = (isset($this->translatedRoutes[$module][$defaultPresenter]) ? $this->translatedRoutes[$module][$defaultPresenter]['mask'][$locale] : null);
			$flags = Route::SECURED;
			$metadata = array(
				'presenter' => [Route::VALUE => $defaultPresenter],
				'action' => [Route::VALUE => $defaultAction],
			);
			$host = $module;
			switch ($host) {
				case self::API:
					$host = self::WWW;
					$metadata['module'] = 'Api';
					$maskPrefix = self::API;
					break;
				case self::ADMIN:
					$metadata['module'] = 'Admin';
					break;
				case self::WEBLEED:
					$flags = Route::ONE_WAY;
					// no break;
				case self::HEARTBLEED:
					$metadata['module'] = 'Webleed';
					break;
				case self::UPC:
					$metadata['module'] = 'UpcKeys';
					break;
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
			if (count($this->supportedLocales[$module]) > 1 && $locale !== $this->translator->getLocale()) {
				$metadata['module'] = (!isset($metadata['module']) ? self::TRANSLATE_THIS : $metadata['module'] . self::TRANSLATE_THIS);
			}
			$this->router[] = new Route("//{$host}.{$this->rootDomainMapping[$locale]}/{$maskPrefix}{$mask}", $metadata, $flags);
		}
	}


}
