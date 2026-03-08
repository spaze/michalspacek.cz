<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\UpcKeys;

use MichalSpacekCz\Test\TestCaseRunner;
use Nette\Utils\Json;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../bootstrap.php';

/** @testCase */
final class WiFiKeyTest extends TestCase
{

	public function testJsonSerializable(): void
	{
		$key = new WiFiKey('Le', 'Serial', 'OUI', 'AB:CD', 'KEY', WiFiBand::Band5GHz);
		$expected = [
			'serial' => 'LeSerial',
			'oui' => 'OUI',
			'mac' => 'AB:CD',
			'key' => 'KEY',
			'type' => '5 GHz',
			'typeId' => 2,
			'serialPrefix' => 'Le',
		];
		Assert::same($expected, Json::decode(Json::encode($key), forceArrays: true));
		Assert::same('OUI', $key->getOui());
		Assert::same('AB:CD', $key->getMac());
	}

}

TestCaseRunner::run(WiFiKeyTest::class);
