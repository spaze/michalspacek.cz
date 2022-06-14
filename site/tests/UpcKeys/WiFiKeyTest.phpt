<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\UpcKeys;

use Nette\Utils\Json;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../bootstrap.php';

/** @testCase */
class WiFiKeyTest extends TestCase
{

	public function testJsonSerializable(): void
	{
		$key = new WiFiKey('LeSerial', 'Le', 'OUI', 'AB:CD', 'KEY', WiFiBand::Band5GHz);
		$expected = [
			'serial' => 'LeSerial',
			'oui' => 'OUI',
			'mac' => 'AB:CD',
			'key' => 'KEY',
			'type' => '5 GHz',
			'typeId' => 2,
			'serialPrefix' => 'Le',
		];
		Assert::same($expected, Json::decode(Json::encode($key), Json::FORCE_ARRAY));
	}

}

(new WiFiKeyTest())->run();
