<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Test;

use MichalSpacekCz\Application\LocaleLinkGenerator;
use MichalSpacekCz\Application\RouterFactory;
use MichalSpacekCz\Application\Routers\BlogPostRoute;
use MichalSpacekCz\Application\Theme;
use MichalSpacekCz\Http\SecurityHeaders;
use MichalSpacekCz\Post\Loader as BlogPostLoader;
use MichalSpacekCz\Test\Http\Request;
use MichalSpacekCz\Test\Http\Response;
use Nette\Application\LinkGenerator;
use Nette\Application\PresenterFactory;
use Nette\Caching\Storages\DevNullStorage;
use Nette\Database\Connection as DatabaseConnection;
use Nette\Database\Explorer as DatabaseExplorer;
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


	public function getDatabase(): DatabaseExplorer
	{
		static $service;
		if (!$service) {
			$service = new DatabaseExplorer($this->getDatabaseConnection(), $this->getDatabaseStructure());
		}
		return $service;
	}


	public function getTranslator(): NoOpTranslator
	{
		static $service;
		if (!$service) {
			$service = new NoOpTranslator();
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


	public function getRouterFactory(): RouterFactory
	{
		static $service;
		if (!$service) {
			$service = new RouterFactory($this->getBlogPostLoader(), $this->getTranslator());
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


	public function getRoute(): BlogPostRoute
	{
		static $service;
		if (!$service) {
			$service = new BlogPostRoute($this->getBlogPostLoader(), '');
		}
		return $service;
	}


	public function getLinkGenerator(): LinkGenerator
	{
		static $service;
		if (!$service) {
			$service = new LinkGenerator($this->getRoute(), new UrlScript());
		}
		return $service;
	}


	public function getLocaleLinkGenerator(): LocaleLinkGenerator
	{
		static $service;
		if (!$service) {
			$service = new LocaleLinkGenerator($this->getRouterFactory(), $this->getHttpRequest(), $this->getPresenterFactory(), $this->getLinkGenerator(), $this->getTranslator());
		}
		return $service;
	}


	public function getSecurityHeaders(): SecurityHeaders
	{
		static $service;
		if (!$service) {
			$service = new SecurityHeaders($this->getHttpRequest(), $this->getHttpResponse(), $this->getCspConfig(), $this->getLocaleLinkGenerator());
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

}
