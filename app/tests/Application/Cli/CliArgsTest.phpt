<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\Application\Cli;

use InvalidArgumentException;
use LogicException;
use MichalSpacekCz\Test\TestCaseRunner;
use Nette\CommandLine\Command;
use Nette\CommandLine\HelpRenderer;
use Nette\CommandLine\Normalizers;
use Nette\CommandLine\Parameters\Parameter;
use Nette\CommandLine\ParseFailure;
use Override;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../../bootstrap.php';

/** @testCase */
final class CliArgsTest extends TestCase
{

	private const string DID_NOT_PARSE = 'The command line did not parse, check getError() first';


	public function testSharedFlags(): void
	{
		$cliArgs = CliArgs::parse(NoCliArgs::class, []);
		Assert::null($cliArgs->getError());
		Assert::false($cliArgs->getFlag(CliArgs::HELP));
		Assert::false($cliArgs->getFlag(CliArgs::DEBUG));
		Assert::false($cliArgs->getFlag(CliArgs::COLORS));

		$cliArgs = CliArgs::parse(NoCliArgs::class, ['--help', '--debug', '--colors']);
		Assert::null($cliArgs->getError());
		Assert::true($cliArgs->getFlag(CliArgs::HELP));
		Assert::true($cliArgs->getFlag(CliArgs::DEBUG));
		Assert::true($cliArgs->getFlag(CliArgs::COLORS));
	}


	public function testHelpIsStandalone(): void
	{
		$cliArgs = CliArgs::parse($this->getPathProvider(), ['--help']); // the required path is not demanded
		Assert::null($cliArgs->getError());
		Assert::true($cliArgs->getFlag(CliArgs::HELP));
		Assert::true(CliArgs::parse($this->getPathProvider(), ['-h'])->getFlag(CliArgs::HELP));

		$error = CliArgs::parse($this->getPathProvider(), ['--help', '--nope'])->getError(); // an unknown option is refused as always
		assert($error !== null);
		Assert::same('Unknown option `--nope`.', $error->getMessage());
	}


	/**
	 * The layout is the library's, pinned here once because bootCli() prints it as it is; the program is named after the running script
	 */
	public function testHelp(): void
	{
		$help = (new HelpRenderer(width: 80))->renderToString(CliArgs::parse($this->getPathProvider(), ['--help'])->getCommand());
		Assert::match(
			"Usage: %S% [options] <path>\n"
			. "\n"
			. "Does something with a path\n"
			. "\n"
			. "Options:\n"
			. "  -h, --help  Print this help\n"
			. "  --debug     Boot with the development config and Tracy in development mode\n"
			. "  --colors    Color the output even when it is not a terminal\n"
			. "\n"
			. "Arguments:\n"
			. "  <path>      An existing path\n",
			$help,
		);

		$help = (new HelpRenderer(width: 80))->renderToString(CliArgs::parse(NoCliArgs::class, [])->getCommand());
		Assert::match("Usage: %S% [options]\n\nOptions:\n%A%", $help); // no description, so no blank block for it
	}


	public function testProviderArgs(): void
	{
		$cliArgs = CliArgs::parse($this->getProvider(), ['--waldo', '--out', 'x', 'bar']);
		Assert::null($cliArgs->getError());
		Assert::true($cliArgs->getFlag('--waldo'));
		Assert::same('x', $cliArgs->getArg('--out'));
		Assert::same('bar', $cliArgs->getArg('foo'));
		Assert::false($cliArgs->getFlag(CliArgs::DEBUG));

		$cliArgs = CliArgs::parse($this->getProvider(), []);
		Assert::false($cliArgs->getFlag('--waldo'));
		Assert::throws(function () use ($cliArgs): void {
			$cliArgs->getArg('--out'); // an option that was not given has no string to return
		}, LogicException::class, 'Parameter --out is not a string');
		Assert::throws(function () use ($cliArgs): void {
			$cliArgs->getArg('foo'); // neither has an optional argument that was not given
		}, LogicException::class, 'Parameter foo is not a string');
	}


	public function testSharedFlagsAreDefinedBeforeTheProviderArgs(): void
	{
		$cliArgs = CliArgs::parse($this->getProvider(), ['--nope']);
		Assert::notNull($cliArgs->getError());
		$names = array_map(static fn (Parameter $parameter): string => $parameter->name, $cliArgs->getCommand()->getParameters()); // the definition is there in the error state too
		Assert::same([CliArgs::HELP, CliArgs::DEBUG, CliArgs::COLORS, '--waldo', '--out', 'foo'], $names);
	}


	public function testNormalizedArg(): void
	{
		Assert::same(realpath(__DIR__), CliArgs::parse($this->getPathProvider(), [__DIR__])->getArg('path'));
	}


	public function testUnknownNameOrAnotherKindIsRefused(): void
	{
		$cliArgs = CliArgs::parse($this->getProvider(), ['bar']);
		Assert::throws(function () use ($cliArgs): void {
			$cliArgs->getFlag('--nope');
		}, InvalidArgumentException::class, "Parameter '--nope' is not defined.");
		Assert::throws(function () use ($cliArgs): void {
			$cliArgs->getFlag('foo');
		}, InvalidArgumentException::class, "Parameter 'foo' is Nette\\CommandLine\\Parameters\\Argument, not Nette\\CommandLine\\Parameters\\Flag.");
		Assert::throws(function () use ($cliArgs): void {
			$cliArgs->getArg('--waldo');
		}, InvalidArgumentException::class, "Parameter '--waldo' is Nette\\CommandLine\\Parameters\\Flag, not Nette\\CommandLine\\Parameters\\ValueParameter.");
	}


	public function testErrorState(): void
	{
		$cliArgs = CliArgs::parse($this->getProvider(), ['--nope']);
		$error = $cliArgs->getError();
		assert($error !== null);
		Assert::same('Unknown option `--nope`.', $error->getMessage());
		Assert::same(ParseFailure::UnknownOption, $error->reason);
		Assert::same('--nope', $error->token);

		$e = Assert::throws(function () use ($cliArgs): void {
			$cliArgs->getFlag(CliArgs::DEBUG);
		}, LogicException::class, self::DID_NOT_PARSE);
		assert($e instanceof LogicException);
		Assert::same($error, $e->getPrevious());
		Assert::throws(function () use ($cliArgs): void {
			$cliArgs->getArg('foo');
		}, LogicException::class, self::DID_NOT_PARSE);
	}


	/**
	 * The wording is the library's, pinned here once because the scripts print it as it is
	 */
	public function testErrorMessages(): void
	{
		$cases = [
			'unknown option' => [$this->getProvider(), ['--frobnicate'], 'Unknown option `--frobnicate`.'],
			'misspelled option' => [$this->getProvider(), ['--walod'], 'Unknown option `--walod`. Did you mean `--waldo`?'],
			'value given to a flag' => [$this->getProvider(), ['--waldo=1'], 'Option `--waldo` does not accept a value.'],
			'too many arguments' => [$this->getProvider(), ['bar', 'baz'], 'Unexpected argument `baz`.'],
			'unexpected argument' => [NoCliArgs::class, ['waldo'], 'Unexpected argument `waldo`.'],
			'missing argument' => [$this->getPathProvider(), [], 'Missing required argument `<path>`.'],
			'empty path' => [$this->getPathProvider(), [''], "Argument `<path>`: file path `''` not found."],
			'nonexistent path' => [$this->getPathProvider(), ['/nonexistent/waldo'], 'Argument `<path>`: file path `/nonexistent/waldo` not found.'],
		];
		$expected = $actual = [];
		foreach ($cases as $case => [$provider, $argv, $message]) {
			$expected[$case] = $message;
			$actual[$case] = CliArgs::parse($provider, $argv)->getError()?->getMessage();
		}
		Assert::same($expected, $actual);
	}


	public function testRedefiningASharedFlagIsRefused(): void
	{
		$provider = new class implements CliArgsProvider {

			#[Override]
			public static function getDescription(): ?string
			{
				return null;
			}


			#[Override]
			public static function defineArgs(Command $command): void
			{
				$command->addFlag(CliArgs::DEBUG);
			}

		};
		Assert::throws(function () use ($provider): void {
			CliArgs::parse($provider::class, []);
		}, InvalidArgumentException::class, "Option '--debug' is already defined.");
	}


	/**
	 * @return class-string<CliArgsProvider>
	 */
	private function getProvider(): string
	{
		$provider = new class implements CliArgsProvider {

			#[Override]
			public static function getDescription(): string
			{
				return 'Does something';
			}


			#[Override]
			public static function defineArgs(Command $command): void
			{
				$command->addFlag('--waldo', 'A flag');
				$command->addOption('--out', 'An option with a value', valueName: 'file');
				$command->addArgument('foo', 'A value', optional: true);
			}

		};
		return $provider::class;
	}


	/**
	 * @return class-string<CliArgsProvider>
	 */
	private function getPathProvider(): string
	{
		$provider = new class implements CliArgsProvider {

			#[Override]
			public static function getDescription(): string
			{
				return 'Does something with a path';
			}


			#[Override]
			public static function defineArgs(Command $command): void
			{
				$command->addArgument('path', 'An existing path', normalizer: Normalizers::realPath(...));
			}

		};
		return $provider::class;
	}

}

TestCaseRunner::run(CliArgsTest::class);
