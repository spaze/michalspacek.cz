<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Application;

use Exception;
use MichalSpacekCz\Application\Cli\CliArgs;
use MichalSpacekCz\Application\Cli\CliArgsProvider;
use Nette\Bootstrap\Configurator;
use Nette\CommandLine\Parser;
use Nette\DI\Compiler;
use Nette\DI\Container;
use Nette\Neon\Neon;
use PHP_Parallel_Lint\PhpConsoleColor\ConsoleColor;
use RuntimeException;
use SensitiveParameter;
use Throwable;
use Tracy\Debugger;

final class Bootstrap
{

	private const string MODE_DEVELOPMENT = 'development';
	private const string SITE_DIR = __DIR__ . '/../..';
	private const string DEBUG = '--debug';
	private const string COLORS = '--colors';


	public static function boot(): Container
	{
		return self::bootWebApp(null, null);
	}


	/**
	 * The web boot with its file dependencies as parameters, for BootstrapTest, which boots the real config
	 * chain: it passes the committed template on machines that have no secrets, and its own temp directory,
	 * because the container cache key includes dynamic parameter names, never values, so a container compiled
	 * into the shared temp/ and checked only against the template's placeholders could be reused by a real
	 * boot with the same key.
	 */
	public static function bootContainerTest(string $secretsFile, string $tempDir): Container
	{
		return self::bootWebApp($secretsFile, $tempDir);
	}


	private static function bootWebApp(?string $secretsFile, ?string $tempDir): Container
	{
		$configurator = self::createConfigurator(
			ServerEnv::tryGetString('ENVIRONMENT') === self::MODE_DEVELOPMENT,
			self::SITE_DIR . '/config/extra-' . ServerEnv::getString('SERVER_NAME') . '.neon',
			tempDir: $tempDir,
		);
		$secrets = self::addSecrets($configurator, true, $secretsFile);
		return self::createContainer($configurator, $secrets);
	}


	/**
	 * @param class-string<CliArgsProvider> $argsProvider
	 */
	public static function bootCli(string $argsProvider): Container
	{
		ServerEnv::setString('HTTPS', 'on');
		$cliArgs = self::getCliArgs($argsProvider);
		$debugMode = ServerEnv::tryGetString('PHP_CLI_ENVIRONMENT') === self::MODE_DEVELOPMENT || $cliArgs->getFlag(self::DEBUG);
		$configurator = self::createConfigurator(
			$debugMode,
			self::SITE_DIR . '/config/' . ($debugMode ? 'extra-cli-debug.neon' : 'extra-cli.neon'),
		);
		$secrets = self::addSecrets($configurator, false);
		$container = self::createContainer($configurator, $secrets);
		if ($cliArgs->getFlag(self::COLORS)) {
			$container->getByType(ConsoleColor::class)->setForceStyle(true);
		}
		$container->addService('cliArgs', $cliArgs);
		return $container;
	}


	public static function bootTest(): Container
	{
		// No addSecrets() here, deliberately: the fakes in tests.neon are static parameters, and a dynamic
		// parameter would override them, running the whole suite against the real keys on any machine that has them
		$configurator = self::createConfigurator(true, finalConfig: self::SITE_DIR . '/config/tests.neon');
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


	private static function createConfigurator(bool $debugMode, ?string $extraConfig = null, ?string $finalConfig = null, ?string $tempDir = null): Configurator
	{
		$configurator = new Configurator();
		$configurator->addStaticParameters(['siteDir' => self::SITE_DIR]);

		$configurator->setDebugMode($debugMode);
		$configurator->enableTracy(self::SITE_DIR . '/log');
		$configurator->setTimeZone('Europe/Prague');
		$configurator->setTempDirectory($tempDir ?? self::SITE_DIR . '/temp');

		$existingFiles = array_filter(self::getConfigurationFiles($extraConfig, $finalConfig), function (?string $path) {
			return $path !== null && is_file($path);
		});
		foreach ($existingFiles as $filename) {
			$configurator->addConfig($filename);
		}

		return $configurator;
	}


	/**
	 * Passes the secrets to the container as dynamic parameters, so the values are handed over at runtime and not
	 * compiled into the container in temp/. Call after createConfigurator(): Tracy is set up there, so a mistake
	 * in the hand-edited secrets file ends up in the exception log.
	 *
	 * @return array<array-key, mixed>|null What was loaded, so createContainer() can check none of it got compiled in
	 */
	private static function addSecrets(Configurator $configurator, bool $required, ?string $secretsFile = null): ?array
	{
		$secrets = self::loadSecrets($required, $secretsFile);
		// Installed even when there's no file, a CLI boot without one say: hiding anything keyed `secrets`
		// doesn't depend on the values, and the compile check can still refuse a stale value it would
		// otherwise dump from the config arrays in the trace
		ContainerSecretsChecker::hideSecrets(Debugger::getBlueScreen(), $secrets ?? []);
		if ($secrets !== null) {
			ContainerSecretsChecker::checkValues($secrets);
			$configurator->addDynamicParameters(['secrets' => $secrets]);
		}
		return $secrets;
	}


	/**
	 * The web app can't do anything useful without the secrets, so a missing file stops it right away with a message
	 * that says what to do (if `$required` is `true`). CLI scripts tolerate a missing file because CI runs some of them
	 * with no secrets.neon anywhere.
	 *
	 * @return array<array-key, mixed>|null
	 */
	private static function loadSecrets(bool $required, ?string $secretsFile = null): ?array
	{
		$secretsFile ??= self::SITE_DIR . '/config/secrets.neon';
		if (!is_file($secretsFile)) {
			if ($required) {
				throw new RuntimeException("Missing {$secretsFile}, copy config/secrets.template.neon there and fill in the real values");
			}
			return null;
		}
		try {
			$secrets = Neon::decodeFile($secretsFile);
		} catch (Throwable $e) {
			// Deliberately not chained, and without the original message: the message quotes the file's tokens,
			// and the original's trace holds the whole file as the decoder's argument, so either would put
			// the secrets in the exception log
			throw new RuntimeException("Loading {$secretsFile} failed with " . $e::class);
		}
		if (!is_array($secrets)) {
			throw new RuntimeException("{$secretsFile} must contain a neon mapping, it contains " . get_debug_type($secrets));
		}
		if ($secrets !== [] && array_is_list($secrets)) {
			// A list would pass as %secrets% and the boot would then die later, on the first service reading
			// a named path like secrets.database.default.password, with an error that never says what's wrong
			throw new RuntimeException("{$secretsFile} must contain a neon mapping keyed by the secret names, it contains a list");
		}
		if ($secrets === []) {
			// An empty mapping would replace the whole declared %secrets% tree with nothing, and the boot would
			// then die later, on the first service reading a secret, with an error that never says what's missing
			throw new RuntimeException("{$secretsFile} is empty, fill in the real values, see config/secrets.template.neon");
		}
		return $secrets;
	}


	/**
	 * The check runs only when the container is really compiled: `onCompile` fires from `generateContainer()`,
	 * which `ContainerLoader` calls just when there is no container to load, so a cached boot costs nothing.
	 *
	 * @param array<array-key, mixed>|null $secrets
	 */
	private static function createContainer(#[SensitiveParameter] Configurator $configurator, #[SensitiveParameter] ?array $secrets): Container
	{
		$configurator->onCompile[] = function (#[SensitiveParameter] Configurator $configurator, #[SensitiveParameter] Compiler $compiler) use ($secrets): void {
			$compiler->addExtension('containerSecretsChecker', new ContainerSecretsChecker($secrets ?? []));
		};
		return $configurator->createContainer();
	}


	/**
	 * @param class-string<CliArgsProvider> $argsProvider
	 */
	private static function getCliArgs(string $argsProvider): CliArgs
	{
		$cliArgsParser = new Parser();
		$argsProvider::defineArgs($cliArgsParser);
		$cliArgsParser->addSwitch(self::DEBUG); // --debug and --colors are available to every script, but can be readded there for readability
		$cliArgsParser->addSwitch(self::COLORS);
		$cliArgsParsed = [];
		$cliArgsError = null;
		try {
			foreach ($cliArgsParser->parse() as $key => $value) {
				if (is_string($value) || $value === true || $value === null) {
					$cliArgsParsed[$key] = $value;
				}
			}
		} catch (Exception $e) {
			$cliArgsError = $e->getMessage();
		}
		return new CliArgs($cliArgsParsed, $cliArgsError);
	}

}
