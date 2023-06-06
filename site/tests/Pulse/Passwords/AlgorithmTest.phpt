<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Pulse\Passwords;

use DateTime;
use Tester\Assert;
use Tester\TestCase;

$runner = require __DIR__ . '/../../bootstrap.php';

/** @testCase */
class AlgorithmTest extends TestCase
{

	private const ALGO = 'bcrypt';


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
		$attributes = new AlgorithmAttributes($inner, $outer, null);
		$algorithm = new Algorithm('1', self::ALGO, self::ALGO, true, true, new DateTime(), true, $attributes, null, $disclosure);
		Assert::same($expected, $algorithm->getFullAlgo());
	}

}

$runner->run(AlgorithmTest::class);
