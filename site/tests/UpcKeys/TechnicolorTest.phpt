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
use MichalSpacekCz\UpcKeys\Exceptions\UpcKeysApiUnknownPrefixException;
use Nette\Utils\Json;
use Nette\Utils\JsonException;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../bootstrap.php';

/** @testCase */
class TechnicolorTest extends TestCase
{

	public function __construct(
		private readonly Database $database,
		private readonly Technicolor $technicolor,
		private readonly NullLogger $logger,
		private readonly HttpClientMock $httpClient,
	) {
	}


	protected function tearDown(): void
	{
		$this->database->reset();
		$this->logger->reset();
	}


	public function testGetKeysUnknownPrefix(): void
	{
		$this->database->setFetchAllDefaultResult([
			[
				'serial' => 'UGHH303',
				'key' => 'baz',
				'type' => WiFiBand::Band24GHz->value,
			],
		]);
		$this->technicolor->getKeys('UPCwhateva');
		$this->database->setFetchAllDefaultResult([
			[
				'serial' => '303',
				'key' => 'baz',
				'type' => WiFiBand::Band24GHz->value,
			],
		]);
		$this->technicolor->getKeys('UPCwhateva');
		Assert::count(2, $this->logger->getLogged());
		Assert::type(UpcKeysApiUnknownPrefixException::class, $this->logger->getLogged()[0]);
		Assert::type(UpcKeysApiUnknownPrefixException::class, $this->logger->getLogged()[1]);
	}


	public function testGetKeys(): void
	{
		$this->database->setFetchAllDefaultResult([
			[
				'serial' => 'SAPP808',
				'key' => 'B4R',
				'type' => WiFiBand::Band5GHz->value,
			],
			[
				'serial' => 'SAAP303',
				'key' => 'F00',
				'type' => WiFiBand::Band24GHz->value,
			],
			[
				'serial' => 'SAAP123',
				'key' => '456',
				'type' => WiFiBand::Band24GHz->value,
			],
		]);
		$expected = [
			new WiFiKey('SAAP123', 'SAAP', null, null, '456', WiFiBand::Band24GHz),
			new WiFiKey('SAAP303', 'SAAP', null, null, 'F00', WiFiBand::Band24GHz),
			new WiFiKey('SAPP808', 'SAPP', null, null, 'B4R', WiFiBand::Band5GHz),
		];
		$keys = $this->technicolor->getKeys('UPC1234567');
		Assert::same([], $this->logger->getLogged());
		Assert::equal($expected, $keys);
	}


	public function testGetKeysGenerateAndStore(): void
	{
		$ssid = 'UPC1234567';
		$this->httpClient->setGetResult(Json::encode(''));
		Assert::same([], $this->technicolor->getKeys($ssid));
		Assert::same([], $this->logger->getLogged());
		Assert::count(0, $this->database->getParamsArrayForQuery('INSERT INTO ssids'));

		$this->httpClient->setGetResult(Json::encode("SBAP303,foo,1\n\nSBAP808,bar,2"));
		$keys = $this->technicolor->getKeys($ssid);
		$expected = [
			new WiFiKey('SBAP303', 'SBAP', null, null, 'foo', WiFiBand::Band24GHz),
			new WiFiKey('SBAP808', 'SBAP', null, null, 'bar', WiFiBand::Band5GHz),
		];
		$expectedParams = [
			['key_ssid' => '', 'serial' => 'SBAP303', 'key' => 'foo', 'type' => 1],
			['key_ssid' => '', 'serial' => 'SBAP808', 'key' => 'bar', 'type' => 2],
		];
		Assert::same([], $this->logger->getLogged());
		Assert::equal($expected, $keys);
		Assert::same($ssid, $this->database->getParamsArrayForQuery('INSERT INTO ssids')[0]['ssid']);
		Assert::same($expectedParams, $this->database->getParamsArrayForQuery('INSERT INTO `keys`'));
	}


	public function testGetKeysGenerateAndStoreNoJson(): void
	{
		$this->httpClient->setGetResult('');
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
		$this->httpClient->setGetResult($json);
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
		$this->httpClient->setGetResult($json);
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
