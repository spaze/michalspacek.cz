<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Application;

use MichalSpacekCz\Http\SecurityHeaders;
use Nette\Application\Application;
use Nette\Application\Request;
use Nette\Application\Response;
use Nette\Application\UI\Presenter;
use Nette\Bootstrap\Configurator;
use Nette\DI\Container;
use Nette\Http\IResponse;
use Nette\SmartObject;

class Bootstrap
{

	use SmartObject;


	/** @var string */
	private const MODE_PRODUCTION = 'production';

	/** @var string */
	private const MODE_DEVELOPMENT = 'development';

	private Container $container;

	private IResponse $httpResponse;

	private SecurityHeaders $securityHeaders;

	private string $siteDir;

	private string $logDir;

	private string $tempDir;

	private string $environment;

	private string $timeZone;


	public function __construct(string $siteDir, string $logDir, string $tempDir, ?string $environment, string $timeZone)
	{
		$this->siteDir = $siteDir;
		$this->logDir = $logDir;
		$this->tempDir = $tempDir;
		$this->environment = $environment ?? self::MODE_PRODUCTION;
		$this->timeZone = $timeZone;
	}


	public function run(): void
	{
		$configurator = new Configurator();
		$configurator->addParameters(['siteDir' => $this->siteDir]);

		$configurator->setDebugMode($this->isDebugMode());
		$configurator->enableDebugger($this->logDir);
		$configurator->setTimeZone($this->timeZone);
		$configurator->setTempDirectory($this->tempDir);

		$configurator->createRobotLoader()
			->addDirectory($this->siteDir . '/app')
			->register();

		$existingFiles = array_filter($this->getConfigurationFiles(), function ($path) {
			return is_file($path);
		});
		foreach ($existingFiles as $filename) {
			$configurator->addConfig($filename);
		}

		$this->container = $configurator->createContainer();
		$this->httpResponse = $this->container->getByType(IResponse::class);
		$this->securityHeaders = $this->container->getByType(SecurityHeaders::class);
		$this->redirectToSecure();

		$application = $this->container->getByType(Application::class);
		$application->onRequest[] = function (Application $sender, Request $request): void {
			$action = $request->getParameter(Presenter::ACTION_KEY) ?? Presenter::DEFAULT_ACTION;
			$this->securityHeaders->setCsp($request->getPresenterName(), $action);
		};
		$application->onResponse[] = function (Application $sender, Response $response): void {
			$this->securityHeaders->sendHeaders();
		};
		$application->run();
	}


	/**
	 * @return string[]
	 */
	private function getConfigurationFiles(): array
	{
		return array_unique(array(
			$this->siteDir . '/config/extensions.neon',
			$this->siteDir . '/config/common.neon',
			$this->siteDir . '/config/contentsecuritypolicy.neon',
			$this->siteDir . '/config/parameters.neon',
			$this->siteDir . '/config/presenters.neon',
			$this->siteDir . '/config/services.neon',
			$this->siteDir . '/config/routes.neon',
			$this->siteDir . '/config/extra-' . $_SERVER['SERVER_NAME'] . '.neon',
			$this->siteDir . '/config/local.neon',
		));
	}


	private function isDebugMode(): bool
	{
		return ($this->environment === self::MODE_DEVELOPMENT);
	}


	private function redirectToSecure(): void
	{
		$fqdn = $this->container->getParameters()['domain']['fqdn'];
		$uri = $_SERVER['REQUEST_URI'];
		if ($_SERVER['HTTP_HOST'] !== $fqdn) {
			$this->securityHeaders->setDefaultCsp()->sendHeaders();
			$this->httpResponse->redirect("https://{$fqdn}{$uri}", IResponse::S301_MOVED_PERMANENTLY);
			exit();
		}
	}

}
