<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Utils;

use MichalSpacekCz\Test\TestCaseRunner;
use MichalSpacekCz\Utils\Exceptions\JsonItemNotStringException;
use MichalSpacekCz\Utils\Exceptions\JsonItemsNotArrayException;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../bootstrap.php';

/** @testCase */
class JsonUtilsTest extends TestCase
{

	public function __construct(
		private readonly JsonUtils $jsonUtils,
	) {
	}


	public function testDecodeListOfStringsItemNotString(): void
	{
		Assert::exception(function (): void {
			$this->jsonUtils->decodeListOfStrings('["foo",303]');
		}, JsonItemNotStringException::class, "Item is of type 'int', not a string (JSON: '[\"foo\",303]')");
	}


	public function testDecodeListOfStringsItemsNotArray(): void
	{
		Assert::exception(function (): void {
			$this->jsonUtils->decodeListOfStrings('"foo"');
		}, JsonItemsNotArrayException::class, "The items array is actually a string not an array (JSON: '\"foo\"')");
	}

}

TestCaseRunner::run(JsonUtilsTest::class);
