<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Application;

use Nette\Bootstrap\Configurator;
use Nette\DI\Container;
use Nette\Utils\Arrays;

class Bootstrap
{

	private const MODE_DEVELOPMENT = 'development';


	public function __construct(
		private string $siteDir,
	) {
	}


	public function boot(): Container
	{
		return $this->createConfigurator(
			($_SERVER['ENVIRONMENT'] ?? null) === self::MODE_DEVELOPMENT,
			$this->siteDir . '/config/extra-' . $_SERVER['SERVER_NAME'] . '.neon',
		)->createContainer();
	}


	public function bootCli(): Container
	{
		$_SERVER['HTTPS'] = 'on';
		$debugMode = ($_SERVER['PHP_CLI_ENVIRONMENT'] ?? null) === self::MODE_DEVELOPMENT || Arrays::contains($_SERVER['argv'], '--debug');
		return $this->createConfigurator(
			$debugMode,
			$this->siteDir . '/config/' . ($debugMode ? 'extra-cli-debug.neon' : 'extra-cli.neon'),
		)->createContainer();
	}


	/**
	 * @return string[]
	 */
	private function getConfigurationFiles(string $extraConfig): array
	{
		return array_unique(array(
			$this->siteDir . '/config/extensions.neon',
			$this->siteDir . '/config/common.neon',
			$this->siteDir . '/config/contentsecuritypolicy.neon',
			$this->siteDir . '/config/parameters.neon',
			$this->siteDir . '/config/presenters.neon',
			$this->siteDir . '/config/services.neon',
			$this->siteDir . '/config/routes.neon',
			$extraConfig,
			$this->siteDir . '/config/local.neon',
		));
	}


	private function createConfigurator(bool $debugMode, string $extraConfig): Configurator
	{
		$configurator = new Configurator();
		$configurator->addParameters(['siteDir' => $this->siteDir]);

		$configurator->setDebugMode($debugMode);
		$configurator->enableDebugger($this->siteDir . '/log');
		$configurator->setTimeZone('Europe/Prague');
		$configurator->setTempDirectory($this->siteDir . '/temp');

		$configurator->createRobotLoader()
			->addDirectory($this->siteDir . '/app')
			->register();

		$existingFiles = array_filter($this->getConfigurationFiles($extraConfig), function ($path) {
			return is_file($path);
		});
		foreach ($existingFiles as $filename) {
			$configurator->addConfig($filename);
		}

		return $configurator;
	}

}
