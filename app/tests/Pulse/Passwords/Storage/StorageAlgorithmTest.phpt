<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Pulse\Passwords\Storage;

use DateTime;
use MichalSpacekCz\Pulse\Passwords\Algorithms\PasswordHashingAlgorithm;
use MichalSpacekCz\Test\TestCaseRunner;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../../../bootstrap.php';

/** @testCase */
final class StorageAlgorithmTest extends TestCase
{

	private const string ALGO = 'bcrypt';


	/**
	 * @return list<array{inner:list<string>|null, outer:list<string>|null, expected:string|null}>
	 */
	public function getInnerOuterExpected(): array
	{
		return [
			[
				'inner' => null,
				'outer' => null,
				'expected' => null,
			],
			[
				'inner' => ['inner'],
				'outer' => null,
				'expected' => self::ALGO . '(inner(password))',
			],
			[
				'inner' => ['inner1', 'inner2'],
				'outer' => null,
				'expected' => self::ALGO . '(inner2(inner1(password)))',
			],
			[
				'inner' => null,
				'outer' => ['outer'],
				'expected' => 'outer(' . self::ALGO . '(password))',
			],
			[
				'inner' => null,
				'outer' => ['outer1', 'outer2'],
				'expected' => 'outer2(outer1(' . self::ALGO . '(password)))',
			],
			[
				'inner' => ['inner1', 'inner2'],
				'outer' => ['outer1', 'outer2'],
				'expected' => 'outer2(outer1(' . self::ALGO . '(inner2(inner1(password)))))',
			],
		];
	}


	/**
	 * @param list<string>|null $inner
	 * @param list<string>|null $outer
	 * @param string|null $expected
	 * @dataProvider getInnerOuterExpected
	 */
	public function testGetFullAlgo(?array $inner, ?array $outer, ?string $expected): void
	{
		$disclosure = new StorageDisclosure(123, 'https://example.com/', 'https://archive.example.com', null, new DateTime('yesterday'), new DateTime(), 'type', 'docs');
		$attributes = new StorageAlgorithmAttributes($inner, $outer, null);
		$algorithm = new StorageAlgorithm('1', new PasswordHashingAlgorithm(21, self::ALGO, self::ALGO, true, true), new DateTime(), true, $attributes, null, $disclosure);
		Assert::same($expected, $algorithm->getFullAlgo());
	}

}

TestCaseRunner::run(StorageAlgorithmTest::class);
