<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Training\Trainings;

use MichalSpacekCz\Test\Database\Database;
use MichalSpacekCz\Test\TestCaseRunner;
use Override;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../../bootstrap.php';

/** @testCase */
final class TrainingsTest extends TestCase
{

	public function __construct(
		private readonly Trainings $trainings,
		private readonly Database $database,
	) {
	}


	#[Override]
	protected function tearDown(): void
	{
		$this->database->reset();
	}


	public function testGetActionById(): void
	{
		$this->database->setFetchFieldDefaultResult('pulled pork');
		Assert::same('pulled pork', $this->trainings->getActionById(303));
	}


	public function testDeletePersonalData(): void
	{
		$this->trainings->deletePersonalData([123, 246]);
		$params = $this->database->getParamsArrayForQuery('UPDATE training_applications SET ? WHERE key_date IN (?)');
		Assert::same([
			'name' => null,
			'email' => null,
			'company' => null,
			'street' => null,
			'city' => null,
			'zip' => null,
			'country' => null,
			'company_id' => null,
			'company_tax_id' => null,
			'note' => null,
			'access_token' => null,
		], $params[0]);
		Assert::same([123, 246], $params[1]);
	}

}

TestCaseRunner::run(TrainingsTest::class);
