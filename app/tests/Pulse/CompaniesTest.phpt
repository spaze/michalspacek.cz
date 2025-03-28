<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\Pulse;

use MichalSpacekCz\Test\Database\Database;
use MichalSpacekCz\Test\TestCaseRunner;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../bootstrap.php';

/** @testCase */
final class CompaniesTest extends TestCase
{

	public function __construct(
		private readonly Companies $companies,
		private readonly Database $database,
	) {
	}


	public function testGetAll(): void
	{
		$this->database->setFetchAllDefaultResult([
			[
				'id' => 123,
				'name' => 'One',
				'tradeName' => 'dba',
				'alias' => 'one',
				'sortName' => 'sort-one',
			],
			[
				'id' => 456,
				'name' => 'Two',
				'tradeName' => null,
				'alias' => 'two',
				'sortName' => 'sort-two',
			],
		]);
		$all = $this->companies->getAll();
		Assert::same(123, $all[0]->getId());
		Assert::same('One', $all[0]->getCompanyName());
		Assert::same('dba', $all[0]->getTradeName());
		Assert::same('one', $all[0]->getCompanyAlias());
		Assert::same('sort-one', $all[0]->getSortName());
		Assert::same(456, $all[1]->getId());
		Assert::same('Two', $all[1]->getCompanyName());
		Assert::null($all[1]->getTradeName());
		Assert::same('two', $all[1]->getCompanyAlias());
		Assert::same('sort-two', $all[1]->getSortName());
	}


	public function testGetByName(): void
	{
		$this->database->setFetchDefaultResult([
			'id' => 123,
			'name' => 'One',
			'tradeName' => 'dba',
			'alias' => 'one',
			'sortName' => 'sort-one',
		]);
		$company = $this->companies->getByName('foo');
		assert($company instanceof Company);
		Assert::same(123, $company->getId());
		Assert::same('One', $company->getCompanyName());
		Assert::same('dba', $company->getTradeName());
		Assert::same('one', $company->getCompanyAlias());
		Assert::same('sort-one', $company->getSortName());
	}

}

TestCaseRunner::run(CompaniesTest::class);
