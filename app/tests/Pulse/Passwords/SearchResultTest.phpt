<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\Pulse\Passwords;

use DateTime;
use MichalSpacekCz\Pulse\Company;
use MichalSpacekCz\Pulse\Passwords\Algorithms\PasswordHashingAlgorithm;
use MichalSpacekCz\Pulse\Passwords\Storage\StorageAlgorithm;
use MichalSpacekCz\Pulse\Passwords\Storage\StorageAlgorithmAttributes;
use MichalSpacekCz\Pulse\Passwords\Storage\StorageDisclosure;
use MichalSpacekCz\Pulse\Passwords\Storage\StorageWildcardSite;
use MichalSpacekCz\Test\TestCaseRunner;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../../bootstrap.php';

/** @testCase */
final class SearchResultTest extends TestCase
{

	public function __construct(
		private readonly Rating $rating,
	) {
	}


	public function testIsCompanyNameMatch(): void
	{
		$result = new SearchResult();
		$company = $this->makeCompany();
		Assert::false($result->isCompanyNameMatch($company));
		$result->addCompanyNameMatch($company);
		Assert::true($result->isCompanyNameMatch($company));
	}


	public function testIsTradeNameMatch(): void
	{
		$result = new SearchResult();
		$company = $this->makeCompany();
		Assert::false($result->isTradeNameMatch($company));
		$result->addTradeNameMatch($company);
		Assert::true($result->isTradeNameMatch($company));
	}


	public function testIsAlgorithmNameMatch(): void
	{
		$result = new SearchResult();
		$algorithm = $this->makeAlgorithm();
		Assert::false($result->isAlgorithmNameMatch($algorithm));
		$result->addAlgorithmNameMatch($algorithm);
		Assert::true($result->isAlgorithmNameMatch($algorithm));
	}


	public function testIsSiteUrlMatch(): void
	{
		$result = new SearchResult();
		$site = $this->makeSite();
		Assert::false($result->isSiteUrlMatch($site));
		$result->addSiteUrlMatch($site);
		Assert::true($result->isSiteUrlMatch($site));
	}


	public function testIsSiteAliasMatch(): void
	{
		$result = new SearchResult();
		$site = $this->makeSite();
		Assert::false($result->isSiteAliasMatch($site));
		$result->addSiteAliasMatch($site);
		Assert::true($result->isSiteAliasMatch($site));
	}


	public function testIsDisclosureUrlMatch(): void
	{
		$result = new SearchResult();
		$disclosure = $this->makeDisclosure();
		Assert::false($result->isDisclosureUrlMatch($disclosure));
		$result->addDisclosureUrlMatch($disclosure);
		Assert::true($result->isDisclosureUrlMatch($disclosure));
	}


	public function testIsAlgorithmDisclosureUrlMatch(): void
	{
		$result = new SearchResult();
		$disclosure = $this->makeDisclosure();
		$algorithm = $this->makeAlgorithm($disclosure);
		Assert::false($result->isAlgorithmDisclosureUrlMatch($algorithm));
		$result->addDisclosureUrlMatch($disclosure);
		Assert::true($result->isAlgorithmDisclosureUrlMatch($algorithm));
	}


	public function testIsDisclosureHistoryMatch(): void
	{
		$result = new SearchResult();
		Assert::false($result->isDisclosureHistoryMatch());
		$result->markDisclosureHistoryMatch();
		Assert::true($result->isDisclosureHistoryMatch());
	}


	private function makeCompany(): Company
	{
		return new Company(1, 'Foo Inc.', null, 'foo', 'Foo');
	}


	private function makeAlgorithm(?StorageDisclosure $disclosure = null): StorageAlgorithm
	{
		return new StorageAlgorithm(
			'1',
			new PasswordHashingAlgorithm(21, 'bcrypt', 'bcrypt', true, true),
			new DateTime(),
			true,
			new StorageAlgorithmAttributes(null, null, null),
			null,
			$disclosure ?? $this->makeDisclosure(),
		);
	}


	private function makeDisclosure(): StorageDisclosure
	{
		return new StorageDisclosure(1, 'https://example.com/', 'https://archive.example.com', null, new DateTime('yesterday'), new DateTime(), 'type', 'docs');
	}


	private function makeSite(): StorageWildcardSite
	{
		return new StorageWildcardSite($this->rating, 's1', $this->makeCompany(), 'storage-1', $this->makeAlgorithm());
	}

}

TestCaseRunner::run(SearchResultTest::class);
