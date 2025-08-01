<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\UpcKeys;

use MichalSpacekCz\Test\Database\Database;
use MichalSpacekCz\Test\Http\Client\HttpClientMock;
use MichalSpacekCz\Test\NullLogger;
use MichalSpacekCz\Test\TestCaseRunner;
use MichalSpacekCz\UpcKeys\Exceptions\UpcKeysApiIncorrectTokensException;
use MichalSpacekCz\UpcKeys\Exceptions\UpcKeysApiResponseInvalidException;
use Nette\Utils\Json;
use Nette\Utils\JsonException;
use Override;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../bootstrap.php';

/** @testCase */
final class TechnicolorTest extends TestCase
{

	public function __construct(
		private readonly Database $database,
		private readonly Technicolor $technicolor,
		private readonly NullLogger $logger,
		private readonly HttpClientMock $httpClient,
	) {
	}


	#[Override]
	protected function tearDown(): void
	{
		$this->database->reset();
		$this->logger->reset();
	}


	public function testGetKeys(): void
	{
		$this->database->setFetchAllDefaultResult([
			[
				'prefixId' => 1,
				'serial' => 808,
				'key' => 0x822570411,
				'type' => WiFiBand::Band5GHz->value,
			],
			[
				'prefixId' => 0,
				'serial' => 303,
				'key' => 0x2B9CE2B9CE,
				'type' => WiFiBand::Band24GHz->value,
			],
			[
				'prefixId' => 0,
				'serial' => 123,
				'key' => 0x852F602C6E,
				'type' => WiFiBand::Band24GHz->value,
			],
		]);
		$expected = [
			new WiFiKey('SAAP', '00000123', null, null, 'QUXWALDO', WiFiBand::Band24GHz),
			new WiFiKey('SAAP', '00000303', null, null, 'FOOOFOOO', WiFiBand::Band24GHz),
			new WiFiKey('SAPP', '00000808', null, null, 'BARFOBAR', WiFiBand::Band5GHz),
		];
		$keys = $this->technicolor->getKeys('UPC1234567');
		Assert::same([], $this->logger->getLogged());
		Assert::equal($expected, $keys);
	}


	public function testGetKeysGenerateAndStore(): void
	{
		$ssid = 'UPC1234567';
		$this->httpClient->setResponse(Json::encode(''));
		Assert::same([], $this->technicolor->getKeys($ssid));
		Assert::same([], $this->logger->getLogged());
		Assert::count(0, $this->database->getParamsArrayForQuery('INSERT INTO ssids'));

		$this->httpClient->setResponse(Json::encode("SBAP303,FOOOFOOO,1\n\nSBAP808,BAARBAAR,2"));
		$keys = $this->technicolor->getKeys($ssid);
		$expected = [
			new WiFiKey('SBAP', '00000303', null, null, 'FOOOFOOO', WiFiBand::Band24GHz),
			new WiFiKey('SBAP', '00000808', null, null, 'BAARBAAR', WiFiBand::Band5GHz),
		];
		$expectedParams = [
			['key_ssid' => '', 'prefix_id' => 2, 'serial' => 303, 'key' => 0X2B9CE2B9CE, 'type' => 1],
			['key_ssid' => '', 'prefix_id' => 2, 'serial' => 808, 'key' => 0x801108011, 'type' => 2],
		];
		Assert::same([], $this->logger->getLogged());
		Assert::equal($expected, $keys);
		Assert::same($ssid, $this->database->getParamsArrayForQuery('INSERT INTO ssids')[0]['ssid']);
		Assert::same($expectedParams, $this->database->getParamsArrayForQuery('INSERT INTO `keys`'));
	}


	public function testGetKeysGenerateAndStoreNoJson(): void
	{
		$this->httpClient->setResponse('');
		$keys = $this->technicolor->getKeys('UPC1234568');
		Assert::same([], $keys);
		$exception = $this->logger->getLogged()[0];
		if (!$exception instanceof UpcKeysApiResponseInvalidException) {
			Assert::fail('Exception is of a wrong type ' . get_debug_type($exception));
		} else {
			Assert::same('Invalid API response', $exception->getMessage());
			Assert::type(JsonException::class, $exception->getPrevious());
		}
	}


	public function testGetKeysGenerateAndStoreBadJson(): void
	{
		$json = Json::encode(['303', 808]);
		$this->httpClient->setResponse($json);
		$keys = $this->technicolor->getKeys('UPC1234568');
		Assert::same([], $keys);
		$exception = $this->logger->getLogged()[0];
		if (!$exception instanceof UpcKeysApiResponseInvalidException) {
			Assert::fail('Exception is of a wrong type ' . get_debug_type($exception));
		} else {
			Assert::same("Invalid API response: {$json}", $exception->getMessage());
			Assert::null($exception->getPrevious());
		}
	}


	public function testGetKeysGenerateAndStoreIncorrectTokens(): void
	{
		$line = "SBAP303,foo,not-a-number";
		$json = Json::encode($line);
		$this->httpClient->setResponse($json);
		$keys = $this->technicolor->getKeys('UPC1234568');
		Assert::same([], $keys);
		$exception = $this->logger->getLogged()[0];
		if (!$exception instanceof UpcKeysApiIncorrectTokensException) {
			Assert::fail('Exception is of a wrong type ' . get_debug_type($exception));
		} else {
			Assert::same("Invalid API response: {$json} ({$line})", $exception->getMessage());
		}
	}


	public function testGetModelWithPrefixes(): void
	{
		Assert::same(['Technicolor TC7200' => ['SAAP', 'SAPP', 'SBAP']], $this->technicolor->getModelWithPrefixes());
	}

}

TestCaseRunner::run(TechnicolorTest::class);
