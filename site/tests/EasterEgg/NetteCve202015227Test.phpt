<?php
declare(strict_types = 1);

namespace MichalSpacekCz\EasterEgg;

use MichalSpacekCz\Test\TestCaseRunner;
use Nette\Application\BadRequestException;
use Nette\Application\Routers\Route;
use Nette\Application\Routers\RouteList;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../bootstrap.php';

/** @testCase */
class NetteCve202015227Test extends TestCase
{

	public function __construct(
		private readonly NetteCve202015227 $cve202015227,
	) {
	}


	public function testRceUnknownCallback(): void
	{
		Assert::exception(function (): void {
			$this->cve202015227->rce('foo', ['bar' => 'baz']);
		}, BadRequestException::class, "[MichalSpacekCz\EasterEgg\NetteCve202015227] Unknown callback 'foo'");
	}


	public function testRceEmptyParam(): void
	{
		Assert::exception(function (): void {
			$this->cve202015227->rce('exec', ['bar' => 'baz']);
		}, BadRequestException::class, "[MichalSpacekCz\EasterEgg\NetteCve202015227] Empty param 'command' for callback 'exec'");
	}


	public function testRceUnknownValue(): void
	{
		Assert::exception(function (): void {
			$this->cve202015227->rce('exec', ['command' => 'baz']);
		}, BadRequestException::class, "[MichalSpacekCz\EasterEgg\NetteCve202015227] Unknown value 'baz' for callback 'exec' and param 'command'");
	}


	public function testRceLs(): void
	{
		$rce = $this->cve202015227->rce('exec', ['command' => 'ls foo']);
		Assert::same(NetteCve202015227View::Ls, $rce->view);
	}


	public function testRceIfconfig(): void
	{
		$rce = $this->cve202015227->rce('exec', ['command' => 'ifconfig bar']);
		Assert::same(NetteCve202015227View::Ifconfig, $rce->view);
		Assert::type('string', $rce->eth0RxPackets);
		Assert::type('string', $rce->eth1RxPackets);
		Assert::type('string', $rce->loRxPackets);
		Assert::type('string', $rce->eth0RxBytes);
		Assert::type('string', $rce->eth1RxBytes);
		Assert::type('string', $rce->loRxBytes);
		Assert::type('string', $rce->eth0TxPackets);
		Assert::type('string', $rce->eth1TxPackets);
		Assert::type('string', $rce->loTxPackets);
		Assert::type('string', $rce->eth0TxBytes);
		Assert::type('string', $rce->eth1TxBytes);
		Assert::type('string', $rce->loTxBytes);
	}


	public function testRceWget(): void
	{
		$rce = $this->cve202015227->rce('shell_exec', ['cmd' => 'wget example.com']);
		Assert::same(NetteCve202015227View::Wget, $rce->view);
	}


	/** @dataProvider getCommands */
	public function testRceNotFound(NetteCve202015227View $view, string $command, string $cmd): void
	{
		$rce = $this->cve202015227->rce('shell_exec', ['cmd' => $cmd]);
		Assert::same($view, $rce->view);
		Assert::same($command, $rce->command);
	}


	public function testAddRoute(): void
	{
		$routeList = new RouteList();
		$this->cve202015227->addRoute($routeList);
		$routeLists = $routeList->getRouters();
		if (!isset($routeLists[0]) || !$routeLists[0] instanceof RouteList) {
			Assert::fail('There should be at least one RouteList');
		} else {
			Assert::same('EasterEgg:', $routeLists[0]->getModule());
			$routers = $routeLists[0]->getRouters();
			if (!isset($routers[0]) || !$routers[0] instanceof Route) {
				Assert::fail('There should be at least one Route in the RouteList');
			} else {
				Assert::same(['presenter' => 'Nette', 'action' => 'micro'], $routers[0]->getDefaults());
			}
		}
	}


	/**
	 * @return list<array{view:NetteCve202015227View, command:string, cmd:string}>
	 */
	public function getCommands(): array
	{
		return [
			[
				'view' => NetteCve202015227View::NotFound,
				'command' => 'echo',
				'cmd' => 'echo something',
			],
			[
				'view' => NetteCve202015227View::NotFound,
				'command' => 'bash',
				'cmd' => 'bash something',
			],
			[
				'view' => NetteCve202015227View::NotFound,
				'command' => 'zsh',
				'cmd' => 'sh something',
			],
			[
				'view' => NetteCve202015227View::NotRecognized,
				'command' => 'certutil.exe',
				'cmd' => 'certutil something',
			],
			[
				'view' => NetteCve202015227View::NotRecognized,
				'command' => 'sa.exe',
				'cmd' => 'sa.exe something',
			],
		];
	}

}

TestCaseRunner::run(NetteCve202015227Test::class);
