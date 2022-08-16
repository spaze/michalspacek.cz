<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Application;

use Nette\Bootstrap\Configurator;
use Nette\DI\Container;
use Nette\Utils\Arrays;
use PHP_Parallel_Lint\PhpConsoleColor\ConsoleColor;

class Bootstrap
{

	private const MODE_DEVELOPMENT = 'development';

	private static string $siteDir;


	public static function boot(string $siteDir): Container
	{
		self::$siteDir = $siteDir;
		return self::createConfigurator(
			($_SERVER['ENVIRONMENT'] ?? null) === self::MODE_DEVELOPMENT,
			self::$siteDir . '/config/extra-' . $_SERVER['SERVER_NAME'] . '.neon',
		)->createContainer();
	}


	public static function bootCli(string $siteDir): Container
	{
		self::$siteDir = $siteDir;
		$_SERVER['HTTPS'] = 'on';
		$debugMode = ($_SERVER['PHP_CLI_ENVIRONMENT'] ?? null) === self::MODE_DEVELOPMENT || Arrays::contains($_SERVER['argv'], '--debug');
		$container = self::createConfigurator(
			$debugMode,
			self::$siteDir . '/config/' . ($debugMode ? 'extra-cli-debug.neon' : 'extra-cli.neon'),
		)->createContainer();
		if (Arrays::contains($_SERVER['argv'], '--colors')) {
			$container->getByType(ConsoleColor::class)->setForceStyle(true);
		}
		return $container;
	}


	/**
	 * @return string[]
	 */
	private static function getConfigurationFiles(string $extraConfig): array
	{
		return array_unique([
			self::$siteDir . '/config/extensions.neon',
			self::$siteDir . '/config/common.neon',
			self::$siteDir . '/config/contentsecuritypolicy.neon',
			self::$siteDir . '/config/parameters.neon',
			self::$siteDir . '/config/presenters.neon',
			self::$siteDir . '/config/services.neon',
			self::$siteDir . '/config/routes.neon',
			$extraConfig,
			self::$siteDir . '/config/local.neon',
		]);
	}


	private static function createConfigurator(bool $debugMode, string $extraConfig): Configurator
	{
		$configurator = new Configurator();
		$configurator->addParameters(['siteDir' => self::$siteDir]);

		$configurator->setDebugMode($debugMode);
		$configurator->enableDebugger(self::$siteDir . '/log');
		$configurator->setTimeZone('Europe/Prague');
		$configurator->setTempDirectory(self::$siteDir . '/temp');

		$configurator->createRobotLoader()
			->addDirectory(self::$siteDir . '/app')
			->register();

		$existingFiles = array_filter(self::getConfigurationFiles($extraConfig), function ($path) {
			return is_file($path);
		});
		foreach ($existingFiles as $filename) {
			$configurator->addConfig($filename);
		}

		return $configurator;
	}

}
