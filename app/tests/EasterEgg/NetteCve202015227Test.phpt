<?php
declare(strict_types = 1);

namespace MichalSpacekCz\EasterEgg;

use MichalSpacekCz\Test\Application\ApplicationPresenter;
use MichalSpacekCz\Test\Http\Response;
use MichalSpacekCz\Test\TestCaseRunner;
use Nette\Application\BadRequestException;
use Nette\Application\Routers\Route;
use Nette\Application\Routers\RouteList;
use Nette\Application\UI\Component;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../bootstrap.php';

/** @testCase */
final class NetteCve202015227Test extends TestCase
{

	public function __construct(
		private readonly NetteCve202015227 $cve202015227,
		private readonly ApplicationPresenter $applicationPresenter,
		private readonly Response $httpResponse,
	) {
	}


	public function testRceNoCallback(): void
	{
		$rce = $this->cve202015227->rce(null, $this->createComponent([]));
		Assert::same(NetteCve202015227View::MidnightCommander, $rce->view);
		Assert::hasKey('hint', $rce->parameters);
		Assert::hasKey('files', $rce->parameters);
		Assert::hasKey('emptyLines', $rce->parameters);
		Assert::same('noindex', $this->httpResponse->getHeader('X-Robots-Tag'));
	}


	public function testRceUnknownCallback(): void
	{
		Assert::exception(function (): void {
			$this->cve202015227->rce('foo', $this->createComponent(['bar' => 'baz']));
		}, BadRequestException::class, "[MichalSpacekCz\EasterEgg\NetteCve202015227] Unknown callback 'foo'");
		Assert::same('noindex', $this->httpResponse->getHeader('X-Robots-Tag'));
	}


	public function testRceEmptyParam(): void
	{
		Assert::exception(function (): void {
			$this->cve202015227->rce('exec', $this->createComponent(['bar' => 'baz']));
		}, BadRequestException::class, "[MichalSpacekCz\EasterEgg\NetteCve202015227] Empty param 'command' for callback 'exec'");
	}


	public function testRceUnknownValue(): void
	{
		Assert::exception(function (): void {
			$this->cve202015227->rce('exec', $this->createComponent(['command' => 'baz']));
		}, BadRequestException::class, "[MichalSpacekCz\EasterEgg\NetteCve202015227] Unknown value 'baz' for callback 'exec' and param 'command'");
	}


	public function testRceLs(): void
	{
		$rce = $this->cve202015227->rce('exec', $this->createComponent(['command' => 'ls foo']));
		Assert::same(NetteCve202015227View::Ls, $rce->view);
		Assert::same('noindex', $this->httpResponse->getHeader('X-Robots-Tag'));
	}


	public function testRceIfconfig(): void
	{
		$rce = $this->cve202015227->rce('exec', $this->createComponent(['command' => 'ifconfig bar']));
		Assert::same(NetteCve202015227View::Ifconfig, $rce->view);
		Assert::type('string', $rce->parameters['eth0RxPackets']);
		Assert::type('string', $rce->parameters['eth1RxPackets']);
		Assert::type('string', $rce->parameters['loRxPackets']);
		Assert::type('string', $rce->parameters['eth0RxBytes']);
		Assert::type('string', $rce->parameters['eth1RxBytes']);
		Assert::type('string', $rce->parameters['loRxBytes']);
		Assert::type('string', $rce->parameters['eth0TxPackets']);
		Assert::type('string', $rce->parameters['eth1TxPackets']);
		Assert::type('string', $rce->parameters['loTxPackets']);
		Assert::type('string', $rce->parameters['eth0TxBytes']);
		Assert::type('string', $rce->parameters['eth1TxBytes']);
		Assert::type('string', $rce->parameters['loTxBytes']);
	}


	public function testRceWget(): void
	{
		$rce = $this->cve202015227->rce('shell_exec', $this->createComponent(['cmd' => 'wget example.com']));
		Assert::same(NetteCve202015227View::Wget, $rce->view);
	}


	/** @dataProvider getCommands */
	public function testRceNotFound(NetteCve202015227View $view, string $command, string $cmd): void
	{
		$rce = $this->cve202015227->rce('shell_exec', $this->createComponent(['cmd' => $cmd]));
		Assert::same($view, $rce->view);
		Assert::same($command, $rce->parameters['command']);
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


	/**
	 * @param array<string, string> $params
	 */
	private function createComponent(array $params): Component
	{
		$component = $this->applicationPresenter->createUiPresenter('Www:Homepage', 'Www:Homepage', 'default');
		$component->loadState($params);
		return $component;
	}

}

TestCaseRunner::run(NetteCve202015227Test::class);
