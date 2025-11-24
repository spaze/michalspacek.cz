<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Application\Cli;

use LogicException;
use MichalSpacekCz\Test\TestCaseRunner;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../../bootstrap.php';

/** @testCase */
final class CliArgsTest extends TestCase
{

	public function testCliArgs(): void
	{
		$cliArgs = new CliArgs([], null);
		Assert::null($cliArgs->getError());
		Assert::false($cliArgs->getFlag('foo'));
		Assert::throws(function () use ($cliArgs): void {
			$cliArgs->getArg('foo');
		}, LogicException::class, 'Argument foo is not defined by the args provider');

		$cliArgs = new CliArgs(['foo' => 'bar', 'waldo' => true, 'quux' => null], 'error');
		Assert::same('error', $cliArgs->getError());
		Assert::false($cliArgs->getFlag('foo'));
		Assert::same('bar', $cliArgs->getArg('foo'));
		Assert::true($cliArgs->getFlag('waldo'));
		Assert::throws(function () use ($cliArgs): void {
			$cliArgs->getArg('waldo');
		}, LogicException::class, 'Argument waldo is not a string');
		Assert::false($cliArgs->getFlag('quux'));
		Assert::throws(function () use ($cliArgs): void {
			$cliArgs->getArg('quux');
		}, LogicException::class, 'Argument quux is not a string');
	}

}

TestCaseRunner::run(CliArgsTest::class);
