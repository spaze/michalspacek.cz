<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Test;

use MichalSpacekCz\Application\Routers\BlogPostRoute;
use MichalSpacekCz\Application\Theme;
use MichalSpacekCz\DateTime\DateTimeFormatter;
use MichalSpacekCz\Formatter\TexyFormatter;
use MichalSpacekCz\Formatter\TexyPhraseHandler;
use MichalSpacekCz\Http\SecurityHeaders;
use MichalSpacekCz\Post\Loader as BlogPostLoader;
use MichalSpacekCz\Post\LocaleUrls as BlogPostLocaleUrls;
use MichalSpacekCz\Tags\Tags;
use MichalSpacekCz\Templating\Filters;
use MichalSpacekCz\Templating\TemplateFactory;
use MichalSpacekCz\Test\Application\LocaleLinkGenerator;
use MichalSpacekCz\Test\Database\Database;
use MichalSpacekCz\Test\Http\Request;
use MichalSpacekCz\Test\Http\Response;
use MichalSpacekCz\Test\Latte\LatteFactory;
use MichalSpacekCz\Training\Dates;
use MichalSpacekCz\Training\Locales;
use MichalSpacekCz\Training\Prices;
use MichalSpacekCz\Training\Statuses;
use Nette\Application\Application;
use Nette\Application\PresenterFactory;
use Nette\Bridges\ApplicationLatte\TemplateFactory as NetteTemplateFactory;
use Nette\Caching\Storages\DevNullStorage;
use Nette\Database\Connection as DatabaseConnection;
use Nette\Database\Structure as DatabaseStructure;
use Nette\Http\UrlScript;
use Spaze\ContentSecurityPolicy\Config as CspConfig;
use Spaze\NonceGenerator\Generator as NonceGenerator;
use Tracy\Debugger;

trait ServicesTrait
{

	public function getHttpRequest(): Request
	{
		static $service;
		if (!$service) {
			$service = new Request(new UrlScript());
		}
		return $service;
	}


	public function getHttpResponse(): Response
	{
		static $service;
		if (!$service) {
			$service = new Response();
		}
		return $service;
	}


	public function getNonceGenerator(): NonceGenerator
	{
		static $service;
		if (!$service) {
			$service = new NonceGenerator();
		}
		return $service;
	}


	public function getCspConfig(): CspConfig
	{
		static $service;
		if (!$service) {
			$service = new CspConfig(self::getNonceGenerator());
		}
		return $service;
	}


	public function getDatabaseConnection(): DatabaseConnection
	{
		static $service;
		if (!$service) {
			$service = new DatabaseConnection('', null, null, ['lazy' => true]);
		}
		return $service;
	}


	public function getCacheStorage(): DevNullStorage
	{
		static $service;
		if (!$service) {
			$service = new DevNullStorage();
		}
		return $service;
	}


	public function getDatabaseStructure(): DatabaseStructure
	{
		static $service;
		if (!$service) {
			$service = new DatabaseStructure($this->getDatabaseConnection(), $this->getCacheStorage());
		}
		return $service;
	}


	public function getDatabase(): Database
	{
		static $service;
		if (!$service) {
			$service = new Database($this->getDatabaseConnection(), $this->getDatabaseStructure());
		}
		return $service;
	}


	public function getTranslator(): NoOpTranslator
	{
		static $service;
		if (!$service) {
			$service = new NoOpTranslator();
			$service->setDefaultLocale('cs_CZ');
		}
		return $service;
	}


	public function getBlogPostLoader(): BlogPostLoader
	{
		static $service;
		if (!$service) {
			$service = new BlogPostLoader($this->getDatabase(), $this->getTranslator());
		}
		return $service;
	}


	public function getPresenterFactory(): PresenterFactory
	{
		static $service;
		if (!$service) {
			$service = new PresenterFactory();
		}
		return $service;
	}


	public function getApplication(): Application
	{
		static $service;
		if (!$service) {
			$service = new Application($this->getPresenterFactory(), $this->getRoute(), $this->getHttpRequest(), $this->getHttpResponse());
		}
		return $service;
	}


	public function getRoute(): BlogPostRoute
	{
		static $service;
		if (!$service) {
			$service = new BlogPostRoute($this->getBlogPostLoader(), '');
		}
		return $service;
	}


	public function getLocaleLinkGenerator(): LocaleLinkGenerator
	{
		static $service;
		if (!$service) {
			$service = new LocaleLinkGenerator();
		}
		return $service;
	}


	/**
	 * @param array<string|string[]> $permissionsPolicy
	 */
	public function getSecurityHeaders(array $permissionsPolicy): SecurityHeaders
	{
		static $service;
		if (!$service) {
			$service = new SecurityHeaders($this->getHttpRequest(), $this->getHttpResponse(), $this->getCspConfig(), $this->getLocaleLinkGenerator(), $permissionsPolicy);
		}
		return $service;
	}


	public function getTheme(): Theme
	{
		static $service;
		if (!$service) {
			$service = new Theme($this->getHttpRequest(), $this->getHttpResponse());
		}
		return $service;
	}


	public function getLogger(): NullLogger
	{
		static $service;
		if (!$service) {
			$service = new NullLogger();
			Debugger::setLogger($service);
		}
		return $service;
	}


	public function getLocales(): Locales
	{
		static $service;
		if (!$service) {
			$service = new Locales($this->getDatabase());
		}
		return $service;
	}


	public function getTags(): Tags
	{
		static $service;
		if (!$service) {
			$service = new Tags();
		}
		return $service;
	}


	public function getBlogPostLocaleUrls(): BlogPostLocaleUrls
	{
		static $service;
		if (!$service) {
			$service = new BlogPostLocaleUrls($this->getDatabase(), $this->getTags());
		}
		return $service;
	}


	public function getLatteFactory(): LatteFactory
	{
		static $service;
		if (!$service) {
			$service = new LatteFactory();
		}
		return $service;
	}


	public function getStatuses(): Statuses
	{
		static $service;
		if (!$service) {
			$service = new Statuses($this->getDatabase());
		}
		return $service;
	}


	public function getPrices(): Prices
	{
		static $service;
		if (!$service) {
			$service = new Prices(0.21);
		}
		return $service;
	}


	public function getDateTimeFormatter(): DateTimeFormatter
	{
		static $service;
		if (!$service) {
			$service = new DateTimeFormatter($this->getTranslator()->getDefaultLocale());
		}
		return $service;
	}


	public function getDates(): Dates
	{
		static $service;
		if (!$service) {
			$service = new Dates($this->getDatabase(), $this->getStatuses(), $this->getPrices(), $this->getDateTimeFormatter(), $this->getTranslator());
		}
		return $service;
	}


	public function getTexyPhraseHandler(): TexyPhraseHandler
	{
		static $service;
		if (!$service) {
			$service = new TexyPhraseHandler($this->getApplication(), $this->getLocales(), $this->getLocaleLinkGenerator(), $this->getBlogPostLocaleUrls(), $this->getTranslator());
		}
		return $service;
	}


	public function getTexyFormatter(): TexyFormatter
	{
		static $service;
		if (!$service) {
			$service = new TexyFormatter($this->getCacheStorage(), $this->getTranslator(), $this->getDates(), $this->getPrices(), $this->getDateTimeFormatter(), $this->getTexyPhraseHandler(), '/', 'i', '/var/www');
		}
		return $service;
	}


	public function getFilters(): Filters
	{
		static $service;
		if (!$service) {
			$service = new Filters($this->getTexyFormatter(), $this->getDateTimeFormatter());
		}
		return $service;
	}


	public function getNetteTemplateFactory(): NetteTemplateFactory
	{
		static $service;
		if (!$service) {
			$service = new NetteTemplateFactory($this->getLatteFactory());
		}
		return $service;
	}


	public function getTemplateFactory(): TemplateFactory
	{
		static $service;
		if (!$service) {
			$service = new TemplateFactory($this->getTheme(), $this->getFilters(), $this->getTranslator(), $this->getNetteTemplateFactory(), $this->getNonceGenerator());
		}
		return $service;
	}

}
