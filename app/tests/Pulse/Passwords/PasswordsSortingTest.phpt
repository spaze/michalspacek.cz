<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\Pulse\Passwords;

use DateTime;
use MichalSpacekCz\Pulse\Company;
use MichalSpacekCz\Pulse\Passwords\Algorithms\PasswordHashingAlgorithm;
use MichalSpacekCz\Pulse\Passwords\Storage\Storage;
use MichalSpacekCz\Pulse\Passwords\Storage\StorageAlgorithm;
use MichalSpacekCz\Pulse\Passwords\Storage\StorageAlgorithmAttributes;
use MichalSpacekCz\Pulse\Passwords\Storage\StorageDisclosure;
use MichalSpacekCz\Pulse\Passwords\Storage\StorageRegistry;
use MichalSpacekCz\Pulse\Passwords\Storage\StorageSpecificSite;
use MichalSpacekCz\Test\TestCaseRunner;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../../bootstrap.php';

/** @testCase */
final class PasswordsSortingTest extends TestCase
{

	private const string RANDOM_STRING_ID = '123';
	private const int RANDOM_INT_ID = 456;


	public function __construct(
		private readonly PasswordsSorting $passwordsSorting,
		private readonly Rating $rating,
	) {
	}


	/**
	 * @return array<string, array{0:string, 1:array<string, array<int, string>>}>
	 */
	public function getExpected(): array
	{
		return [
			'rating A-F' => [
				'rating-a-f',
				[
					'Company A' => [10 => 'A'],
					'Company B' => [20 => 'A'],
					'Company C' => [40 => 'A', 30 => 'A'],
				],
			],
			'rating F-A' => [
				'rating-f-a',
				[
					'Company A' => [10 => 'A'],
					'Company B' => [20 => 'A'],
					'Company C' => [40 => 'A', 30 => 'A'],
				],
			],
			'newest disclosures first' => [
				'newest-disclosures-first',
				[
					'Company B' => [20 => 'A'],
					'Company C' => [40 => 'A', 30 => 'A'],
					'Company A' => [10 => 'A'],
				],
			],
			'newest disclosures last' => [
				'newest-disclosures-last',
				[
					'Company A' => [10 => 'A'],
					'Company C' => [30 => 'A', 40 => 'A'],
					'Company B' => [20 => 'A'],
				],
			],
			'newly added first' => [
				'newly-added-first',
				[
					'Company A' => [10 => 'A'],
					'Company C' => [30 => 'A', 40 => 'A'],
					'Company B' => [20 => 'A'],
				],
			],
			'newly added last' => [
				'newly-added-last',
				[
					'Company B' => [20 => 'A'],
					'Company C' => [40 => 'A', 30 => 'A'],
					'Company A' => [10 => 'A'],
				],
			],
		];
	}


	/**
	 * @param array<string, array<int, string>> $expected
	 * @dataProvider getExpected
	 */
	public function testSort(string $sort, array $expected): void
	{
		$companyA = new Company(1, 'Company A', null, 'company-a', 'company-a');
		$companyB = new Company(2, 'Company B', null, 'company-b', 'company-b');
		$companyC = new Company(3, 'Company C', null, 'company-c', 'company-c');
		$hashingAlgorithmA = new PasswordHashingAlgorithm(self::RANDOM_INT_ID, 'bcrypt', 'bcrypt', true, true);
		$hashingAlgorithmB = new PasswordHashingAlgorithm(self::RANDOM_INT_ID, 'scrypt', 'scrypt', true, true);
		$attributes = new StorageAlgorithmAttributes([], [], []);
		$storageDisclosureA = new StorageDisclosure(self::RANDOM_INT_ID, 'https://disclosure-a.example', 'https://archive-a.example', null, new DateTime('6 days ago'), new DateTime('3 days ago'), 'docs', 'docs');
		$storageDisclosureB = new StorageDisclosure(self::RANDOM_INT_ID, 'https://disclosure-b.example', 'https://archive-b.example', null, new DateTime('3 days ago'), new DateTime('6 days ago'), 'docs', 'docs');
		$storageAlgorithmA = new StorageAlgorithm(self::RANDOM_STRING_ID, $hashingAlgorithmA, null, false, $attributes, null, $storageDisclosureA);
		$storageAlgorithmB = new StorageAlgorithm(self::RANDOM_STRING_ID, $hashingAlgorithmB, null, false, $attributes, null, $storageDisclosureB);
		$siteA = new StorageSpecificSite($this->rating, '10', 'https://site-a.example', 'site-a', [], $companyA, self::RANDOM_STRING_ID, $storageAlgorithmA);
		$siteB = new StorageSpecificSite($this->rating, '20', 'https://site-b.example', 'site-b', [], $companyB, self::RANDOM_STRING_ID, $storageAlgorithmB);
		$siteC = new StorageSpecificSite($this->rating, '30', 'https://site-c.example', 'site-c', [], $companyC, self::RANDOM_STRING_ID, $storageAlgorithmA);
		$siteD = new StorageSpecificSite($this->rating, '40', 'https://d.example', 'site-d', [], $companyC, self::RANDOM_STRING_ID, $storageAlgorithmB);
		$storageA = new Storage('100', self::RANDOM_INT_ID);
		$storageA->addSite($siteA);
		$storageB = new Storage('200', self::RANDOM_INT_ID);
		$storageB->addSite($siteB);
		$storageC = new Storage('300', self::RANDOM_INT_ID);
		$storageC->addSite($siteC);
		$storageD = new Storage('400', self::RANDOM_INT_ID);
		$storageD->addSite($siteD);
		$registry = new StorageRegistry();
		$registry->addCompany($companyA);
		$registry->addCompany($companyC);
		$registry->addCompany($companyB);
		$registry->addSite($siteA);
		$registry->addSite($siteC);
		$registry->addSite($siteB);
		$registry->addSite($siteD);
		$registry->addStorage($storageA);
		$registry->addStorage($storageC);
		$registry->addStorage($storageB);
		$registry->addStorage($storageD);

		$this->assertSorted($registry, $expected, $sort);
	}


	public function testGetDefaultSort(): void
	{
		Assert::same('a-z', $this->passwordsSorting->getDefaultSort());
	}


	/**
	 * @param array<string, array<int, string>> $expected
	 */
	private function assertSorted(StorageRegistry $registry, array $expected, string $sort): void
	{
		$this->passwordsSorting->sort($registry, $sort);
		$actual = [];
		foreach ($registry->getStorages() as $storage) {
			foreach ($storage->getSites() as $site) {
				$actual[$site->getCompany()->getCompanyName()][$site->getId()] = $site->getRating()->name;
			}
		}
		Assert::same($expected, $actual);
	}

}

TestCaseRunner::run(PasswordsSortingTest::class);
