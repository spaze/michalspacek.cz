<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Utils;

use MichalSpacekCz\Test\TestCaseRunner;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../bootstrap.php';

/** @testCase */
final class ArraysTest extends TestCase
{

	public function testFilterEmpty(): void
	{
		$array = [
			'string',
			'',
			'0',
			0,
			1,
			true,
			false,
			null,
			'key1' => 'string',
			'key2' => '',
			'key3' => '0',
			303 => 0,
			808 => 1,
			'1337' => true,
			'1338' => false,
			'1339' => null,
			'array1' => [],
			'array2' => ['a'],
			'array3' => ['a' => []],
			'array4' => [[]],
		];
		$expected = [
			0 => 'string',
			2 => '0',
			4 => 1,
			5 => true,
			'key1' => 'string',
			'key3' => '0',
			808 => 1,
			1337 => true,
			'array2' => ['a'],
			'array3' => ['a' => []],
			'array4' => [[]],
		];
		Assert::same($expected, Arrays::filterEmpty($array));
		Assert::same([], Arrays::filterEmpty([]));
	}

}

TestCaseRunner::run(ArraysTest::class);
