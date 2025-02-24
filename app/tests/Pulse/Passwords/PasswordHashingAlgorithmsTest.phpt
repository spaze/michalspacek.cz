<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\Pulse\Passwords;

use MichalSpacekCz\Pulse\Passwords\Algorithms\PasswordHashingAlgorithms;
use MichalSpacekCz\Test\Database\Database;
use MichalSpacekCz\Test\TestCaseRunner;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../../bootstrap.php';

/** @testCase */
final class PasswordHashingAlgorithmsTest extends TestCase
{

	public function __construct(
		private readonly PasswordHashingAlgorithms $hashingAlgorithms,
		private readonly Database $database,
	) {
	}


	public function testGetAlgorithms(): void
	{
		$this->database->setFetchAllDefaultResult([
			[
				'id' => 123,
				'algo' => 'Bah!crypt',
				'alias' => 'bah-crypt',
				'salted' => 1,
				'stretched' => 1,
			],
			[
				'id' => 404,
				'algo' => 'MD3.14',
				'alias' => 'md314',
				'salted' => 0,
				'stretched' => 0,
			],
		]);
		$all = $this->hashingAlgorithms->getAlgorithms();
		Assert::same(123, $all[0]->getId());
		Assert::same('Bah!crypt', $all[0]->getName());
		Assert::same('bah-crypt', $all[0]->getAlias());
		Assert::true($all[0]->isSalted());
		Assert::true($all[0]->isStretched());
		Assert::same(404, $all[1]->getId());
		Assert::same('MD3.14', $all[1]->getName());
		Assert::same('md314', $all[1]->getAlias());
		Assert::false($all[1]->isSalted());
		Assert::false($all[1]->isStretched());
	}


	public function testGetAlgorithmByName(): void
	{
		$this->database->setFetchDefaultResult([
			'id' => 303,
			'algo' => 'Arr-gone',
			'alias' => 'arr-gone',
			'salted' => 1,
			'stretched' => 0,
		]);
		$algorithm = $this->hashingAlgorithms->getAlgorithmByName('foo');
		if (!$algorithm) {
			Assert::fail('Algorithms should not be null');
		} else {
			Assert::same(303, $algorithm->getId());
			Assert::same('Arr-gone', $algorithm->getName());
			Assert::same('arr-gone', $algorithm->getAlias());
			Assert::true($algorithm->isSalted());
			Assert::false($algorithm->isStretched());
		}
	}

}

TestCaseRunner::run(PasswordHashingAlgorithmsTest::class);
