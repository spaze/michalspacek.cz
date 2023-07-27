<?php
declare(strict_types = 1);

namespace MichalSpacekCz\UpcKeys;

use Tester\Assert;
use Tester\TestCase;

$runner = require __DIR__ . '/../bootstrap.php';

/** @testCase */
class UpcKeysTest extends TestCase
{

	public function __construct(
		private readonly UpcKeys $upcKeys,
	) {
	}


	public function testGetTextResponse(): void
	{
		Assert::same('', $this->upcKeys->getTextResponse(null, null, [])->getSource());
		Assert::same("# Ssid\n", $this->upcKeys->getTextResponse('Ssid', null, [])->getSource());
		Assert::same("# Error: Erreur\n", $this->upcKeys->getTextResponse(null, 'Erreur', [])->getSource());
		Assert::same("# Ssid\n# Error: Erreur\n", $this->upcKeys->getTextResponse('Ssid', 'Erreur', [])->getSource());

		$key1 = new WiFiKey('LeSerial', 'Le', 'OUI', 'AB:CD', 'KEY', WiFiBand::Band5GHz);
		$key2 = new WiFiKey('LeMans', 'Le', 'OUI', 'AB:CD', 'CLÉ', WiFiBand::Band5GHz);
		Assert::same("KEY\nCLÉ\n", $this->upcKeys->getTextResponse(null, null, [$key1, $key2])->getSource());
		Assert::same("# Ssid\nKEY\nCLÉ\n", $this->upcKeys->getTextResponse('Ssid', null, [$key1, $key2])->getSource());
		Assert::same("# Error: Erreur\nKEY\nCLÉ\n", $this->upcKeys->getTextResponse(null, 'Erreur', [$key1, $key2])->getSource());
		Assert::same("# Ssid\n# Error: Erreur\nKEY\nCLÉ\n", $this->upcKeys->getTextResponse('Ssid', 'Erreur', [$key1, $key2])->getSource());
	}

}

$runner->run(UpcKeysTest::class);
