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
	 * Runs a real script in a child process, the only way to hand bootCli() a command line of its own: a wrong one
	 * has to reach the script, which reports it and picks the exit status, instead of stopping the boot
	 */
	public function testWrongCommandLineStillBootsSoTheScriptCanReportIt(): void
	{
		$process = Process::runExecutable(PHP_BINARY, [__DIR__ . '/../../bin/latte-lint.php', '--frobnicate']);
		Assert::same(2, $process->getExitCode());
		Assert::contains('Usage: latte-lint <path>', $process->getStdOutput());
		Assert::contains('Unknown option `--frobnicate`.', $process->getStdOutput());
	}

}

TestCaseRunner::run(BootstrapCliTest::class);
