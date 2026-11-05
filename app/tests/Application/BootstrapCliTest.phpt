<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\Application;

use MichalSpacekCz\Test\TestCaseRunner;
use Nette\Utils\Process;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../bootstrap.php';

/** @testCase */
final class BootstrapCliTest extends TestCase
{

	/**
	 * A wrong command line has to reach the script, which reports it and picks the exit status, instead of stopping the boot
	 */
	public function testWrongCommandLineStillBootsSoTheScriptCanReportIt(): void
	{
		$process = $this->runLatteLint(['--frobnicate']);
		Assert::same(2, $process->getExitCode());
		Assert::contains('Unknown option `--frobnicate`.', $process->getStdOutput());
		Assert::contains('Usage: latte-lint.php [options] <path>', $process->getStdOutput());
	}


	public function testHelpIsAnsweredBeforeTheContainerBoots(): void
	{
		$process = $this->runLatteLint(['--help']); // without the required path, which a standalone flag does not demand
		Assert::same(0, $process->getExitCode());
		Assert::same('', $process->getStdError());
		$output = $process->getStdOutput();
		Assert::contains('Usage: latte-lint.php [options] <path>', $output);
		Assert::contains('Compiles every Latte template', $output); // the description, wrapped to the width
		Assert::contains('  -h, --help ', $output);
		Assert::contains('  --disable-strict-parsing ', $output);
		Assert::notContains('Latte linter', $output); // the script itself never ran
	}


	public function testColorsFlagColorsTheHelpEvenWhenPiped(): void
	{
		$process = $this->runLatteLint(['--help', '--colors']); // the output is a pipe here, and the child gets NO_COLOR too
		Assert::same(0, $process->getExitCode());
		Assert::contains("\e[", $process->getStdOutput());
	}


	/**
	 * Runs the real script in a child process, the only way to hand bootCli() a command line of its own. NO_COLOR keeps
	 * the escape codes out of the output and COLUMNS fixes the width the help is wrapped to, whatever the environment
	 * of the test run says.
	 *
	 * @param list<string> $args
	 */
	private function runLatteLint(array $args): Process
	{
		return Process::runExecutable(PHP_BINARY, [__DIR__ . '/../../bin/latte-lint.php', ...$args], ['NO_COLOR' => '1', 'COLUMNS' => '80'] + getenv());
	}

}

TestCaseRunner::run(BootstrapCliTest::class);
