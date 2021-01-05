<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Pulse\Passwords;

use RuntimeException;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../../bootstrap.php';

/** @testCase */
class RatingTest extends TestCase
{

	private Algorithm $algo;

	private Rating $rating;


	public function __construct()
	{
		$this->algo = new Algorithm();
		$this->rating = new Rating();
	}


	public function testGradeA(): void
	{
		$this->algo->alias = 'bcrypt';
		$this->algo->disclosureTypes = ['docs' => true, 'foo' => true];
		Assert::same('A', $this->rating->get($this->algo));

		$this->algo->alias = 'bcrypt';
		$this->algo->disclosureTypes = ['facebook-private' => true, 'docs' => true];
		Assert::same('A', $this->rating->get($this->algo));

		$this->algo->alias = 'scrypt';
		Assert::same('A', $this->rating->get($this->algo));

		$this->algo->alias = 'pbkdf2';
		Assert::same('A', $this->rating->get($this->algo));

		$this->algo->alias = 'argon2';
		Assert::same('A', $this->rating->get($this->algo));

		$this->algo->alias = 'md5';
		$this->algo->disclosureTypes = ['facebook-private' => true, 'docs' => true];
		Assert::notSame('A', $this->rating->get($this->algo));
	}


	public function testGradeB(): void
	{
		$this->algo->alias = 'bcrypt';
		$this->algo->disclosureTypes = ['facebook-private' => true, 'blog' => true];
		Assert::same('B', $this->rating->get($this->algo));

		$this->algo->alias = 'bcrypt';
		$this->algo->disclosureTypes = ['facebook-private' => true, 'docs' => true];
		Assert::notSame('B', $this->rating->get($this->algo));

		$this->algo->alias = 'scrypt';
		$this->algo->disclosureTypes = ['foo' => true];
		Assert::exception(function () {
			Assert::same('B', $this->rating->get($this->algo));
		}, RuntimeException::class);
	}


	public function testGradeCDE(): void
	{
		$this->algo->alias = 'md5';
		$this->algo->salted = true;
		$this->algo->stretched = true;
		$this->algo->disclosureTypes = ['facebook-private' => true, 'docs' => true];
		Assert::same('C', $this->rating->get($this->algo));
		Assert::notSame('D', $this->rating->get($this->algo));
		Assert::notSame('E', $this->rating->get($this->algo));

		$this->algo->salted = false;
		$this->algo->stretched = true;
		Assert::notSame('C', $this->rating->get($this->algo));
		Assert::notSame('D', $this->rating->get($this->algo));
		Assert::same('E', $this->rating->get($this->algo));

		$this->algo->salted = true;
		$this->algo->stretched = false;
		Assert::notSame('C', $this->rating->get($this->algo));
		Assert::same('D', $this->rating->get($this->algo));
		Assert::notSame('E', $this->rating->get($this->algo));

		$this->algo->salted = false;
		$this->algo->stretched = false;
		Assert::notSame('C', $this->rating->get($this->algo));
		Assert::notSame('D', $this->rating->get($this->algo));
		Assert::same('E', $this->rating->get($this->algo));
	}


	public function testGradeF(): void
	{
		$this->algo->alias = 'plaintext';
		Assert::same('F', $this->rating->get($this->algo));

		$this->algo->alias = 'encrypted';
		Assert::same('F', $this->rating->get($this->algo));
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

}

(new RatingTest())->run();
