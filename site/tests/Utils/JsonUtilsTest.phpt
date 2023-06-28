<?php
/** @noinspection PhpDocMissingThrowsInspection */
/** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection PhpFullyQualifiedNameUsageInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\Utils;

use Tester\TestCase;

$runner = require __DIR__ . '/../bootstrap.php';

/** @testCase */
class JsonUtilsTest extends TestCase
{

	public function __construct(
		private readonly JsonUtils $jsonUtils,
	) {
	}


	/**
	 * @return void
	 * @throws \MichalSpacekCz\Utils\Exceptions\JsonItemNotStringException Item is of type 'int', not a string (JSON: '["foo",303]')
	 */
	public function testDecodeListOfStringsItemNotString(): void
	{
		$this->jsonUtils->decodeListOfStrings('["foo",303]');
	}


	/**
	 * @return void
	 * @throws \MichalSpacekCz\Utils\Exceptions\JsonItemsNotArrayException The items array is actually a string not an array (JSON: '"foo"')
	 */
	public function testDecodeListOfStringsItemsNotArray(): void
	{
		$this->jsonUtils->decodeListOfStrings('"foo"');
	}

}

$runner->run(JsonUtilsTest::class);
