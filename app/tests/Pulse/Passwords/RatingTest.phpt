<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Pulse\Passwords;

use DateTime;
use MichalSpacekCz\Pulse\Passwords\Algorithms\PasswordHashingAlgorithm;
use MichalSpacekCz\Pulse\Passwords\Storage\StorageAlgorithm;
use MichalSpacekCz\Pulse\Passwords\Storage\StorageAlgorithmAttributes;
use MichalSpacekCz\Pulse\Passwords\Storage\StorageDisclosure;
use MichalSpacekCz\Test\TestCaseRunner;
use RuntimeException;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../../bootstrap.php';

/** @testCase */
final class RatingTest extends TestCase
{

	public function __construct(
		private readonly Rating $rating,
	) {
	}


	public function testGradeA(): void
	{
		$algo = $this->getAlgo('bcrypt', true, true, ['docs', 'foo']);
		Assert::same(RatingGrade::A, $this->rating->get($algo));

		$algo = $this->getAlgo('bcrypt', true, true, ['facebook-private', 'docs']);
		Assert::same(RatingGrade::A, $this->rating->get($algo));

		$algo = $this->getAlgo('scrypt', true, true, ['facebook-private', 'docs']);
		Assert::same(RatingGrade::A, $this->rating->get($algo));

		$algo = $this->getAlgo('pbkdf2', true, true, ['facebook-private', 'docs']);
		Assert::same(RatingGrade::A, $this->rating->get($algo));

		$algo = $this->getAlgo('argon2', true, true, ['facebook-private', 'docs']);
		Assert::same(RatingGrade::A, $this->rating->get($algo));

		$algo = $this->getAlgo('md5', true, true, ['facebook-private', 'docs']);
		Assert::notSame(RatingGrade::A, $this->rating->get($algo));
	}


	public function testGradeB(): void
	{
		$algo = $this->getAlgo('bcrypt', true, true, ['facebook-private', 'blog']);
		Assert::same(RatingGrade::B, $this->rating->get($algo));

		$algo = $this->getAlgo('bcrypt', true, true, ['facebook-private', 'docs']);
		Assert::notSame(RatingGrade::B, $this->rating->get($algo));

		$algo = $this->getAlgo('scrypt', true, true, ['foo']);
		Assert::exception(function () use ($algo): void {
			$this->rating->get($algo);
		}, RuntimeException::class);
	}


	public function testGradeCDE(): void
	{
		$algo = $this->getAlgo('md5', true, true, ['facebook-private', 'docs']);
		Assert::same(RatingGrade::C, $this->rating->get($algo));
		Assert::notSame(RatingGrade::D, $this->rating->get($algo));
		Assert::notSame(RatingGrade::E, $this->rating->get($algo));

		$algo = $this->getAlgo('md5', false, true, ['facebook-private', 'docs']);
		Assert::notSame(RatingGrade::C, $this->rating->get($algo));
		Assert::notSame(RatingGrade::D, $this->rating->get($algo));
		Assert::same(RatingGrade::E, $this->rating->get($algo));

		$algo = $this->getAlgo('md5', true, false, ['facebook-private', 'docs']);
		Assert::notSame(RatingGrade::C, $this->rating->get($algo));
		Assert::same(RatingGrade::D, $this->rating->get($algo));
		Assert::notSame(RatingGrade::E, $this->rating->get($algo));

		$algo = $this->getAlgo('md5', false, false, ['facebook-private', 'docs']);
		Assert::notSame(RatingGrade::C, $this->rating->get($algo));
		Assert::notSame(RatingGrade::D, $this->rating->get($algo));
		Assert::same(RatingGrade::E, $this->rating->get($algo));
	}


	public function testGradeF(): void
	{
		$algo = $this->getAlgo('plaintext', false, false, ['facebook-private', 'docs']);
		Assert::same(RatingGrade::F, $this->rating->get($algo));

		$algo = $this->getAlgo('encrypted', false, false, ['facebook-private', 'docs']);
		Assert::same(RatingGrade::F, $this->rating->get($algo));
	}


	public function testSecureStorage(): void
	{
		Assert::true($this->rating->isSecureStorage(RatingGrade::A));
		Assert::true($this->rating->isSecureStorage(RatingGrade::B));
		Assert::false($this->rating->isSecureStorage(RatingGrade::C));
		Assert::false($this->rating->isSecureStorage(RatingGrade::D));
		Assert::false($this->rating->isSecureStorage(RatingGrade::E));
		Assert::false($this->rating->isSecureStorage(RatingGrade::F));
	}


	public function testGetRatings(): void
	{
		Assert::same(['a' => 'A', 'b' => 'B', 'c' => 'C', 'd' => 'D', 'e' => 'E', 'f' => 'F'], $this->rating->getRatings());
	}


	/**
	 * @param non-empty-list<string> $disclosureTypes
	 */
	private function getAlgo(string $alias, bool $salted, bool $stretched, array $disclosureTypes): StorageAlgorithm
	{
		$disclosure = new StorageDisclosure(123, 'https://example.com/', 'https://archive.example.com', null, new DateTime('yesterday'), new DateTime(), 'type', array_shift($disclosureTypes));
		$algorithm = new StorageAlgorithm('1', new PasswordHashingAlgorithm(9, 'foo', $alias, $salted, $stretched), new DateTime(), true, new StorageAlgorithmAttributes(null, null, null), null, $disclosure);
		foreach ($disclosureTypes as $typeAlias) {
			$algorithm->addDisclosure(new StorageDisclosure(123, 'https://example.com/', 'https://archive.example.com', null, new DateTime('yesterday'), new DateTime(), 'type', $typeAlias));
		}
		return $algorithm;
	}

}

TestCaseRunner::run(RatingTest::class);
