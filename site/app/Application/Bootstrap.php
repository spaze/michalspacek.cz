<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Application;

use MichalSpacekCz\Http\SecurityHeaders;
use Nette\Application\Application;
use Nette\Application\IResponse as ApplicationIResponse;
use Nette\Application\Request;
use Nette\Application\UI\Presenter;
use Nette\Configurator;
use Nette\DI\Container;
use Nette\Http\IRequest;
use Nette\Http\IResponse;
use Nette\SmartObject;

class Bootstrap
{

	use SmartObject;


	/** @var string */
	private const MODE_PRODUCTION = 'production';

	/** @var string */
	private const MODE_DEVELOPMENT = 'development';

	/** @var IRequest */
	private $httpRequest;

	/** @var Container */
	private $container;

	/** @var IResponse */
	private $httpResponse;

	/** @var SecurityHeaders */
	private $securityHeaders;

	/** @var string */
	private $appDir;

	/** @var string */
	private $logDir;

	/** @var string */
	private $tempDir;

	/** @var string */
	private $environment;

	/** @var string */
	private $timeZone;


	public function __construct(string $appDir, string $logDir, string $tempDir, ?string $environment, string $timeZone)
	{
		$this->appDir = $appDir;
		$this->logDir = $logDir;
		$this->tempDir = $tempDir;
		$this->environment = $environment ?? self::MODE_PRODUCTION;
		$this->timeZone = $timeZone;
	}


	public function run(): void
	{
		$configurator = new Configurator();
		$configurator->addParameters(['appDir' => $this->appDir]);

		$configurator->setDebugMode($this->isDebugMode());
		$configurator->enableDebugger($this->logDir);
		$configurator->setTimeZone($this->timeZone);
		$configurator->setTempDirectory($this->tempDir);

		$configurator->createRobotLoader()
			->addDirectory($this->appDir)
			->register();

		$existingFiles = array_filter($this->getConfigurationFiles(), function ($path) {
			return is_file($path);
		});
		foreach ($existingFiles as $filename) {
			$configurator->addConfig($filename);
		}

		$this->container = $configurator->createContainer();

		$this->httpRequest = $this->container->getByType(IRequest::class);
		$this->httpResponse = $this->container->getByType(IResponse::class);

		$this->securityHeaders = $this->container->getByType(SecurityHeaders::class);
		$this->redirectToSecure();

		$application = $this->container->getByType(Application::class);
		$application->onRequest[] = function (Application $sender, Request $request): void {
			$action = $request->getParameter(Presenter::ACTION_KEY) ?? Presenter::DEFAULT_ACTION;
			$this->securityHeaders->setCsp($request->getPresenterName(), $action);
		};
		$application->onResponse[] = function (Application $sender, ApplicationIResponse $request): void {
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
			$this->appDir . '/config/extensions.neon',
			$this->appDir . '/config/common.neon',
			$this->appDir . '/config/contentsecuritypolicy.neon',
			$this->appDir . '/config/parameters.neon',
			$this->appDir . '/config/presenters.neon',
			$this->appDir . '/config/services.neon',
			$this->appDir . '/config/routes.neon',
			$this->appDir . '/config/extra-' . $_SERVER['SERVER_NAME'] . '.neon',
			$this->appDir . '/config/local.neon',
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