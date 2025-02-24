<?php
declare(strict_types = 1);

namespace MichalSpacekCz\UpcKeys;

use MichalSpacekCz\Test\Database\Database;
use MichalSpacekCz\Test\TestCaseRunner;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../bootstrap.php';

/** @testCase */
final class UbeeTest extends TestCase
{

	public function __construct(
		private readonly Database $database,
		private readonly Ubee $ubee,
	) {
	}


	public function testGetKeys(): void
	{
		$this->database->setFetchAllDefaultResult([
			[
				'mac' => 0,
				'key' => 0xA81D4100F1,
			],
			[
				'mac' => 92339,
				'key' => 0x2A92B95A99,
			],
			[
				'mac' => 5971354,
				'key' => 0x9CE0E7010C,
			],
			[
				'mac' => 12561771,
				'key' => 0x0DD2A41E68,
			],
		]);
		$getKey = function (string $mac, string $key): WiFiKey {
			return new WiFiKey('UAAP', 'UAAP', '647c34', $mac, $key, WiFiBand::Unknown);
		};
		$expected = [
			$getKey('000000', 'VAOUCAHR'),
			$getKey('0168b3', 'FKJLSWUZ'),
			$getKey('5b1d9a', 'TTQOOAIM'),
			$getKey('bfad6b', 'BXJKIHTI'),
		];
		Assert::equal($expected, $this->ubee->getKeys('UPC4543413'));
	}


	public function testGetModelWithPrefixes(): void
	{
		Assert::same(['Ubee EVW3226' => ['UAAP']], $this->ubee->getModelWithPrefixes());
	}

}

TestCaseRunner::run(UbeeTest::class);
