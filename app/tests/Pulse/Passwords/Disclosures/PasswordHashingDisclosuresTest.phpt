<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Pulse\Passwords\Disclosures;

use MichalSpacekCz\Test\Database\Database;
use MichalSpacekCz\Test\TestCaseRunner;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../../../bootstrap.php';

/** @testCase */
class PasswordHashingDisclosuresTest extends TestCase
{

	public function __construct(
		private readonly PasswordHashingDisclosures $disclosures,
		private readonly Database $database,
	) {
	}


	public function testGetDisclosureTypes(): void
	{
		$this->database->setFetchAllDefaultResult([
			[
				'id' => 123,
				'alias' => 'one',
				'type' => 'type1',
			],
			[
				'id' => 456,
				'alias' => 'two',
				'type' => 'type2',
			],
		]);
		$all = $this->disclosures->getDisclosureTypes();
		Assert::same(123, $all[0]->getId());
		Assert::same('one', $all[0]->getAlias());
		Assert::same('type1', $all[0]->getType());
		Assert::same(456, $all[1]->getId());
		Assert::same('two', $all[1]->getAlias());
		Assert::same('type2', $all[1]->getType());
	}


	public function testGetVisibleDisclosures(): void
	{
		$this->database->setFetchPairsDefaultResult([
			'alias1' => 'type1',
			'alias2' => 'type2',
		]);
		Assert::same(['alias1' => 'type1', 'alias2' => 'type2'], $this->disclosures->getVisibleDisclosures());
	}


	public function testGetInvisibleDisclosures(): void
	{
		$this->database->setFetchPairsDefaultResult([
			'alias1' => 'type1',
			'alias2' => 'type2',
		]);
		Assert::same(['alias1' => 'type1', 'alias2' => 'type2'], $this->disclosures->getInvisibleDisclosures());
	}


	public function testGetDisclosureId(): void
	{
		$this->database->setFetchFieldDefaultResult(null);
		Assert::null($this->disclosures->getDisclosureId('https://example.com/', 'https://archive.example.com/'));

		$this->database->setFetchFieldDefaultResult(123);
		Assert::same(123, $this->disclosures->getDisclosureId('https://example.com/', 'https://archive.example.com/'));
	}


	public function testAddDisclosure(): void
	{
		$this->database->setDefaultInsertId('123');
		$lastInsertId = $this->disclosures->addDisclosure(1, 'https://example.com/', 'https://archive.example.com/', 'note', 'now');
		Assert::same(123, $lastInsertId);
	}

}

TestCaseRunner::run(PasswordHashingDisclosuresTest::class);
