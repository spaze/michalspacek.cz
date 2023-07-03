<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Pulse\Passwords;

use DateTime;
use RuntimeException;
use Tester\Assert;
use Tester\TestCase;

$runner = require __DIR__ . '/../../bootstrap.php';

/** @testCase */
class RatingTest extends TestCase
{

	public function __construct(
		private readonly Rating $rating,
	) {
	}


	public function testGradeA(): void
	{
		$algo = $this->getAlgo('bcrypt', true, true, ['docs', 'foo']);
		Assert::same('A', $this->rating->get($algo));

		$algo = $this->getAlgo('bcrypt', true, true, ['facebook-private', 'docs']);
		Assert::same('A', $this->rating->get($algo));

		$algo = $this->getAlgo('scrypt', true, true, ['facebook-private', 'docs']);
		Assert::same('A', $this->rating->get($algo));

		$algo = $this->getAlgo('pbkdf2', true, true, ['facebook-private', 'docs']);
		Assert::same('A', $this->rating->get($algo));

		$algo = $this->getAlgo('argon2', true, true, ['facebook-private', 'docs']);
		Assert::same('A', $this->rating->get($algo));

		$algo = $this->getAlgo('md5', true, true, ['facebook-private', 'docs']);
		Assert::notSame('A', $this->rating->get($algo));
	}


	public function testGradeB(): void
	{
		$algo = $this->getAlgo('bcrypt', true, true, ['facebook-private', 'blog']);
		Assert::same('B', $this->rating->get($algo));

		$algo = $this->getAlgo('bcrypt', true, true, ['facebook-private', 'docs']);
		Assert::notSame('B', $this->rating->get($algo));

		$algo = $this->getAlgo('scrypt', true, true, ['foo']);
		Assert::exception(function () use ($algo): void {
			$this->rating->get($algo);
		}, RuntimeException::class);
	}


	public function testGradeCDE(): void
	{
		$algo = $this->getAlgo('md5', true, true, ['facebook-private', 'docs']);
		Assert::same('C', $this->rating->get($algo));
		Assert::notSame('D', $this->rating->get($algo));
		Assert::notSame('E', $this->rating->get($algo));

		$algo = $this->getAlgo('md5', false, true, ['facebook-private', 'docs']);
		Assert::notSame('C', $this->rating->get($algo));
		Assert::notSame('D', $this->rating->get($algo));
		Assert::same('E', $this->rating->get($algo));

		$algo = $this->getAlgo('md5', true, false, ['facebook-private', 'docs']);
		Assert::notSame('C', $this->rating->get($algo));
		Assert::same('D', $this->rating->get($algo));
		Assert::notSame('E', $this->rating->get($algo));

		$algo = $this->getAlgo('md5', false, false, ['facebook-private', 'docs']);
		Assert::notSame('C', $this->rating->get($algo));
		Assert::notSame('D', $this->rating->get($algo));
		Assert::same('E', $this->rating->get($algo));
	}


	public function testGradeF(): void
	{
		$algo = $this->getAlgo('plaintext', false, false, ['facebook-private', 'docs']);
		Assert::same('F', $this->rating->get($algo));

		$algo = $this->getAlgo('encrypted', false, false, ['facebook-private', 'docs']);
		Assert::same('F', $this->rating->get($algo));
	}


	public function testSecureStorage(): void
	{
		$rating = [
			'A' => true,
			'B' => true,
			'C' => false,
			'D' => false,
			'E' => false,
			'F' => false,
		];
		foreach ($rating as $grade => $expected) {
			Assert::same($expected, $this->rating->isSecureStorage($grade));
		}
	}


	/**
	 * @param non-empty-list<string> $disclosureTypes
	 */
	private function getAlgo(string $alias, bool $salted, bool $stretched, array $disclosureTypes): Algorithm
	{
		$disclosure = new StorageDisclosure(123, 'https://example.com/', 'https://archive.example.com', null, new DateTime('yesterday'), new DateTime(), 'type', array_shift($disclosureTypes));
		$algorithm = new Algorithm('1', 'foo', $alias, $salted, $stretched, new DateTime(), true, new AlgorithmAttributes(null, null, null), null, $disclosure);
		foreach ($disclosureTypes as $typeAlias) {
			$algorithm->addDisclosure(new StorageDisclosure(123, 'https://example.com/', 'https://archive.example.com', null, new DateTime('yesterday'), new DateTime(), 'type', $typeAlias));
		}
		return $algorithm;
	}

}

$runner->run(RatingTest::class);
