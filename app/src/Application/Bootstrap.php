<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Application;

use Exception;
use MichalSpacekCz\Application\Cli\CliArgs;
use MichalSpacekCz\Application\Cli\CliArgsProvider;
use MichalSpacekCz\ShouldNotHappenException;
use Nette\Bootstrap\Configurator;
use Nette\CommandLine\Parser;
use Nette\DI\Container;
use PHP_Parallel_Lint\PhpConsoleColor\ConsoleColor;

final class Bootstrap
{

	private const string MODE_DEVELOPMENT = 'development';
	private const string SITE_DIR = __DIR__ . '/../..';
	private const string CACHE_DIR_CLI = '%tempDir%/cache-cli';
	private const string DEBUG = '--debug';
	private const string COLORS = '--colors';


	public static function boot(): Container
	{
		return self::createConfigurator(
			ServerEnv::tryGetString('ENVIRONMENT') === self::MODE_DEVELOPMENT,
			self::SITE_DIR . '/config/extra-' . ServerEnv::getString('SERVER_NAME') . '.neon',
		)->createContainer();
	}


	/**
	 * @param class-string<CliArgsProvider> $argsProvider
	 */
	public static function bootCli(string $argsProvider): Container
	{
		ServerEnv::setString('HTTPS', 'on');
		$cliArgs = self::getCliArgs($argsProvider);
		$debugMode = ServerEnv::tryGetString('PHP_CLI_ENVIRONMENT') === self::MODE_DEVELOPMENT || $cliArgs->getFlag(self::DEBUG);
		$container = self::createConfigurator(
			$debugMode,
			self::SITE_DIR . '/config/' . ($debugMode ? 'extra-cli-debug.neon' : 'extra-cli.neon'),
			cacheDir: self::CACHE_DIR_CLI,
		)->createContainer();
		if ($cliArgs->getFlag(self::COLORS)) {
			$container->getByType(ConsoleColor::class)->setForceStyle(true);
		}
		$container->addService('cliArgs', $cliArgs);
		return $container;
	}


	public static function bootTest(): Container
	{
		$configurator = self::createConfigurator(true, finalConfig: self::SITE_DIR . '/config/tests.neon', cacheDir: self::CACHE_DIR_CLI);
		$configurator->addStaticParameters([
			'wwwDir' => self::SITE_DIR . '/tests',
		]);
		return $configurator->createContainer();
	}


	/**
	 * @return non-empty-array<int, string|null>
	 */
	private static function getConfigurationFiles(?string $extraConfig = null, ?string $finalConfig = null): array
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
			$finalConfig,
		]);
	}


	private static function createConfigurator(bool $debugMode, ?string $extraConfig = null, ?string $finalConfig = null, ?string $cacheDir = null): Configurator
	{
		$configurator = new Configurator();
		$configurator->addStaticParameters(['siteDir' => self::SITE_DIR]);

		$configurator->setDebugMode($debugMode);
		$configurator->enableTracy(self::SITE_DIR . '/log');
		$configurator->setTimeZone('Europe/Prague');
		$configurator->setTempDirectory(self::SITE_DIR . '/temp');
		if ($cacheDir !== null) {
			self::setCacheDir($configurator, $cacheDir);
		}

		$existingFiles = array_filter(self::getConfigurationFiles($extraConfig, $finalConfig), function (?string $path) {
			return $path !== null && is_file($path);
		});
		foreach ($existingFiles as $filename) {
			$configurator->addConfig($filename);
		}

		return $configurator;
	}


	/**
	 * @param class-string<CliArgsProvider> $argsProvider
	 */
	private static function getCliArgs(string $argsProvider): CliArgs
	{
		$args = $argsProvider::getArgs();
		$args[] = self::DEBUG;
		$args[] = self::COLORS;
		$cliArgsParser = new Parser("\n " . implode("\n ", $args));
		$cliArgsError = null;
		try {
			$cliArgsParsed = $cliArgsParser->parse();
		} catch (Exception $e) {
			$cliArgsError = $e->getMessage();
		}
		return new CliArgs($cliArgsParsed ?? [], $cliArgsError);
	}


	private static function setCacheDir(Configurator $configurator, string $cacheDir): void
	{
		if (
			isset($configurator->defaultExtensions['cache'])
			&& is_array($configurator->defaultExtensions['cache'])
			&& is_array($configurator->defaultExtensions['cache'][1])
			&& is_string($configurator->defaultExtensions['cache'][1][0])
		) {
			$configurator->defaultExtensions['cache'][1][0] = $cacheDir;
		} else {
			throw new ShouldNotHappenException('$configurator->defaultExtensions[\'cache\'][1][0] is not a string');
		}
	}

}
