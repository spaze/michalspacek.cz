<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\Pulse\Passwords\Storage;

use DateTime;
use MichalSpacekCz\Pulse\Company;
use MichalSpacekCz\Pulse\Passwords\Algorithms\PasswordHashingAlgorithm;
use MichalSpacekCz\Pulse\Passwords\Rating;
use MichalSpacekCz\Test\TestCaseRunner;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../../../bootstrap.php';

/** @testCase */
final class StorageWildcardSiteTest extends TestCase
{

	public function __construct(
		private readonly Rating $rating,
	) {
	}


	public function testGetHistoricalAlgorithmsReturnsAllButLatest(): void
	{
		$site = $this->makeSite('bcrypt', 3);
		Assert::count(2, $site->getHistoricalAlgorithms());
	}


	public function testIsSecureStorageAndNoRecommendationForGradeA(): void
	{
		// bcrypt + docs disclosure → Grade A → secure, no recommendation
		$site = $this->makeSite('bcrypt', 1);
		Assert::true($site->isSecureStorage());
		Assert::null($site->getRecommendation());
	}


	public function testNotSecureStorageAndRecommendationForGradeF(): void
	{
		// plaintext → Grade F → not secure, has recommendation
		$site = $this->makeSite('plaintext', 1);
		Assert::false($site->isSecureStorage());
		Assert::type('string', $site->getRecommendation());
	}


	private function makeSite(string $alias, int $algoCount): StorageSite
	{
		$company = new Company(1, 'Foo Inc.', null, 'foo', 'Foo');
		$site = new StorageWildcardSite($this->rating, 'site-1', $company, 'storage-1', $this->makeAlgorithm($alias, '1'));
		for ($i = 2; $i <= $algoCount; $i++) {
			$site->addAlgorithm($this->makeAlgorithm($alias, (string)$i));
		}
		return $site;
	}


	private function makeAlgorithm(string $alias, string $id): StorageAlgorithm
	{
		$disclosure = new StorageDisclosure(1, 'https://example.com/', 'https://archive.example.com', null, new DateTime('yesterday'), new DateTime(), 'docs', 'docs');
		return new StorageAlgorithm($id, new PasswordHashingAlgorithm(21, 'foo', $alias, true, true), new DateTime(), true, new StorageAlgorithmAttributes(null, null, null), null, $disclosure);
	}

}

TestCaseRunner::run(StorageWildcardSiteTest::class);
