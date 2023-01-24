<?php
/** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection PhpFullyQualifiedNameUsageInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\EasterEgg;

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


	/**
	 * @throws \Nette\Application\BadRequestException [MichalSpacekCz\EasterEgg\NetteCve202015227] Unknown callback 'foo'
	 */
	public function testRceUnknownCallback(): void
	{
		$this->cve202015227->rce('foo', ['bar' => 'baz']);
	}


	/**
	 * @throws \Nette\Application\BadRequestException [MichalSpacekCz\EasterEgg\NetteCve202015227] Empty param 'command' for callback 'exec'
	 */
	public function testRceEmptyParam(): void
	{
		$this->cve202015227->rce('exec', ['bar' => 'baz']);
	}


	/**
	 * @throws \Nette\Application\BadRequestException [MichalSpacekCz\EasterEgg\NetteCve202015227] Unknown value 'baz' for callback 'exec' and param 'command'
	 */
	public function testRceUnknownValue(): void
	{
		$this->cve202015227->rce('exec', ['command' => 'baz']);
	}


	public function testRceLs(): void
	{
		Assert::equal(new NetteCve202015227Rce(NetteCve202015227View::Ls, []), $this->cve202015227->rce('exec', ['command' => 'ls foo']));
	}


	public function testRceIfconfig(): void
	{
		$rce = $this->cve202015227->rce('exec', ['command' => 'ifconfig bar']);
		Assert::same(NetteCve202015227View::Ifconfig, $rce->getView());

		$keys = [
			'eth0RxPackets',
			'eth1RxPackets',
			'loRxPackets',
			'eth0RxBytes',
			'eth1RxBytes',
			'loRxBytes',
			'eth0TxPackets',
			'eth1TxPackets',
			'loTxPackets',
			'eth0TxBytes',
			'eth1TxBytes',
			'loTxBytes',
		];
		Assert::equal($keys, array_keys($rce->getParameters()));
	}


	public function testRceWget(): void
	{
		Assert::equal(new NetteCve202015227Rce(NetteCve202015227View::Wget, []), $this->cve202015227->rce('shell_exec', ['cmd' => 'wget example.com']));
	}


	public function testRceNotFound(): void
	{
		Assert::equal(new NetteCve202015227Rce(NetteCve202015227View::NotFound, ['command' => 'echo']), $this->cve202015227->rce('shell_exec', ['cmd' => 'echo something']));
		Assert::equal(new NetteCve202015227Rce(NetteCve202015227View::NotFound, ['command' => 'bash']), $this->cve202015227->rce('shell_exec', ['cmd' => 'bash something']));
		Assert::equal(new NetteCve202015227Rce(NetteCve202015227View::NotFound, ['command' => 'zsh']), $this->cve202015227->rce('shell_exec', ['cmd' => 'sh something']));
		Assert::equal(new NetteCve202015227Rce(NetteCve202015227View::NotRecognized, ['command' => 'certutil.exe']), $this->cve202015227->rce('shell_exec', ['cmd' => 'certutil something']));
		Assert::equal(new NetteCve202015227Rce(NetteCve202015227View::NotRecognized, ['command' => 'sa.exe']), $this->cve202015227->rce('shell_exec', ['cmd' => 'sa.exe something']));
	}

}

$runner->run(NetteCve202015227Test::class);
