<?php
namespace MichalSpacekCz\Pulse\Passwords;
/**
 * Test: MichalSpacekCz\Pulse\Passwords\Rating.
 *
 * @testCase MichalSpacekCz\Pulse\Passwords\RatingTest
 * @author Michal Špaček
 * @package pulse.michalspacek.cz
 */

use Tester\Assert;

require __DIR__ . '/../../../vendor/autoload.php';

class RatingTest extends \Tester\TestCase
{

	/** @var \MichalSpacekCz\Pulse\Passwords\Algorithm */
	private $algo;

	/** @var \MichalSpacekCz\Pulse\Passwords\Rating */
	private $rating;


	public function __construct()
	{

		require __DIR__ . '/../../../app/models/Pulse/Passwords/Algorithm.php';
		$this->algo = new Algorithm();

		require __DIR__ . '/../../../app/models/Pulse/Passwords/Rating.php';
		$this->rating = new Rating();
	}


	public function testGradeA()
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


	public function testGradeB()
	{
		$this->algo->alias = 'bcrypt';
		$this->algo->disclosureTypes = ['facebook-private' => true, 'blog' => true];
		Assert::same('B', $this->rating->get($this->algo));

		$this->algo->alias = 'bcrypt';
		$this->algo->disclosureTypes = ['facebook-private' => true, 'docs' => true];
		Assert::notSame('B', $this->rating->get($this->algo));

		$this->algo->alias = 'scrypt';
		$this->algo->disclosureTypes = ['foo' => true];
		Assert::exception(function() {
			Assert::same('B', $this->rating->get($this->algo));
		}, \RuntimeException::class);

	}


	public function testGradeCDE()
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


	public function testGradeF()
	{
		$this->algo->alias = 'plaintext';
		Assert::same('F', $this->rating->get($this->algo));

		$this->algo->alias = 'encrypted';
		Assert::same('F', $this->rating->get($this->algo));
	}


	public function testSecureStorage()
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

$testCase = new RatingTest();
$testCase->run();
