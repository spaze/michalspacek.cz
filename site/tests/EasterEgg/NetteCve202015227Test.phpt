<?php
declare(strict_types = 1);

namespace MichalSpacekCz\EasterEgg;

use Nette\Application\BadRequestException;
use Tester\Assert;
use Tester\TestCase;

$runner = require __DIR__ . '/../bootstrap.php';

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

$runner->run(NetteCve202015227Test::class);
