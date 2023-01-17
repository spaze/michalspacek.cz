<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Application;

use Nette\Bootstrap\Configurator;
use Nette\DI\Container;
use Nette\Utils\Arrays;
use PHP_Parallel_Lint\PhpConsoleColor\ConsoleColor;
use Tester\Environment;

class Bootstrap
{

	private const MODE_DEVELOPMENT = 'development';
	private const SITE_DIR = __DIR__ . '/../..';


	public static function boot(): Container
	{
		return self::createConfigurator(
			($_SERVER['ENVIRONMENT'] ?? null) === self::MODE_DEVELOPMENT,
			self::SITE_DIR . '/config/extra-' . $_SERVER['SERVER_NAME'] . '.neon',
		)->createContainer();
	}


	public static function bootCli(): Container
	{
		$_SERVER['HTTPS'] = 'on';
		$debugMode = ($_SERVER['PHP_CLI_ENVIRONMENT'] ?? null) === self::MODE_DEVELOPMENT || Arrays::contains($_SERVER['argv'], '--debug');
		$container = self::createConfigurator(
			$debugMode,
			self::SITE_DIR . '/config/' . ($debugMode ? 'extra-cli-debug.neon' : 'extra-cli.neon'),
		)->createContainer();
		if (Arrays::contains($_SERVER['argv'], '--colors')) {
			$container->getByType(ConsoleColor::class)->setForceStyle(true);
		}
		return $container;
	}


	public static function bootTest(): Container
	{
		$configurator = self::createConfigurator(true, self::SITE_DIR . '/config/tests.neon');
		$configurator->addStaticParameters([
			'wwwDir' => self::SITE_DIR . '/tests',
		]);
		$container = $configurator->createContainer();
		Environment::setup();
		return $container;
	}


	/**
	 * @return string[]
	 */
	private static function getConfigurationFiles(string $extraConfig): array
	{
		return array_unique([
			self::SITE_DIR . '/config/extensions.neon',
			self::SITE_DIR . '/config/common.neon',
			self::SITE_DIR . '/config/contentsecuritypolicy.neon',
			self::SITE_DIR . '/config/parameters.neon',
			self::SITE_DIR . '/config/presenters.neon',
			self::SITE_DIR . '/config/services.neon',
			self::SITE_DIR . '/config/routes.neon',
			$extraConfig,
			self::SITE_DIR . '/config/local.neon',
		]);
	}


	private static function createConfigurator(bool $debugMode, string $extraConfig): Configurator
	{
		$configurator = new Configurator();
		$configurator->addStaticParameters(['siteDir' => self::SITE_DIR]);

		$configurator->setDebugMode($debugMode);
		$configurator->enableTracy(self::SITE_DIR . '/log');
		$configurator->setTimeZone('Europe/Prague');
		$configurator->setTempDirectory(self::SITE_DIR . '/temp');

		$existingFiles = array_filter(self::getConfigurationFiles($extraConfig), function ($path) {
			return is_file($path);
		});
		foreach ($existingFiles as $filename) {
			$configurator->addConfig($filename);
		}

		return $configurator;
	}

}
