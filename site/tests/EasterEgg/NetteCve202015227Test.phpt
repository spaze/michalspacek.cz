<?php
/** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection PhpFullyQualifiedNameUsageInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\EasterEgg;

use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../bootstrap.php';

/**
* @testCase MichalSpacekCz\EasterEgg\NetteCve202015227Test
*/
class NetteCve202015227Test extends TestCase
{

	private NetteCve202015227 $cve202015227;


	protected function setUp()
	{
		$this->cve202015227 = new NetteCve202015227();
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
		Assert::same(['nette.micro-ls', []], $this->cve202015227->rce('exec', ['command' => 'ls foo']));
	}


	public function testRceIfconfig(): void
	{
		$rce = $this->cve202015227->rce('exec', ['command' => 'ifconfig bar']);
		Assert::same('nette.micro-ifconfig', $rce[0]);

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
		Assert::equal($keys, array_keys($rce[1]));
	}

}

(new NetteCve202015227Test())->run();
