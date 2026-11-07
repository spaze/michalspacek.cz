<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\CompanyInfo;

use MichalSpacekCz\CompanyInfo\Exceptions\CompanyInfoException;
use MichalSpacekCz\CompanyInfo\Exceptions\CompanyNotFoundException;
use MichalSpacekCz\Test\CompanyInfo\CompanyRegisterMock;
use MichalSpacekCz\Test\NullLogger;
use MichalSpacekCz\Test\TestCaseRunner;
use Nette\Caching\Cache;
use Nette\Caching\Storages\MemoryStorage;
use Override;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../bootstrap.php';

/** @testCase */
final class CompanyInfoTest extends TestCase
{

	private readonly CompanyInfoDetails $detailsCz;

	private readonly CompanyInfoDetails $detailsSk;

	private readonly CompanyRegisterMock $registerCz;

	private readonly CompanyRegisterMock $registerSk;

	private readonly MemoryStorage $cacheStorage;

	private readonly CompanyInfo $companyInfo;


	public function __construct(
		private readonly NullLogger $logger,
	) {
		$this->detailsCz = new CompanyInfoDetails(200, 'OK', '123', 'CZ123', 'Firma s.r.o.', country: 'cz');
		$this->detailsSk = new CompanyInfoDetails(200, 'OK', '123', 'SK123', 'Firma s.r.o.', country: 'sk');
		$this->registerCz = new CompanyRegisterMock('cz', $this->detailsCz);
		$this->registerSk = new CompanyRegisterMock('sk', $this->detailsSk);
		$this->cacheStorage = new MemoryStorage();
		$this->companyInfo = new CompanyInfo([$this->registerCz, $this->registerSk], $this->cacheStorage);
	}


	#[Override]
	protected function tearDown(): void
	{
		$this->registerCz->reset();
		$this->registerSk->reset();
		$this->cacheStorage->clean([Cache::All => true]);
		$this->logger->reset();
	}


	public function testGetDetailsAsksTheCountrysRegister(): void
	{
		Assert::same($this->detailsSk, $this->companyInfo->getDetails('sk', '123'));
		Assert::same(['123'], $this->registerSk->getCompanyIds());
		Assert::same([], $this->registerCz->getCompanyIds());
	}


	public function testGetDetailsUnsupportedCountry(): void
	{
		$details = $this->companyInfo->getDetails('pl', '123');
		Assert::same(['status' => 500, 'statusMessage' => 'Unsupported country'], $details->jsonSerialize());
		Assert::same([], $this->registerCz->getCompanyIds());
		Assert::same([], $this->registerSk->getCompanyIds());
		Assert::same([], $this->logger->getLogged());
	}


	public function testGetDetailsCachesPerCountryAndCompany(): void
	{
		$this->companyInfo->getDetails('cz', '123');
		Assert::same($this->detailsCz, $this->companyInfo->getDetails('cz', '123'));
		Assert::same(['123'], $this->registerCz->getCompanyIds());

		$this->companyInfo->getDetails('cz', '456');
		$this->companyInfo->getDetails('sk', '123');
		Assert::same(['123', '456'], $this->registerCz->getCompanyIds());
		Assert::same(['123'], $this->registerSk->getCompanyIds());
	}


	public function testGetDetailsNotFound(): void
	{
		$this->registerCz->willThrow(new CompanyNotFoundException());
		$details = $this->companyInfo->getDetails('cz', '123');
		Assert::same(['status' => 400, 'statusMessage' => 'Not found'], $details->jsonSerialize());
		Assert::same([CompanyNotFoundException::class . ': Company not found, cz company id: 123'], $this->logger->getLogged());

		$this->companyInfo->getDetails('cz', '123');
		Assert::same(['123', '123'], $this->registerCz->getCompanyIds(), 'A company not found is not cached');
	}


	public function testGetDetailsRegisterError(): void
	{
		$e = new CompanyInfoException('Company Id is not alphanumeric');
		$this->registerCz->willThrow($e);
		$details = $this->companyInfo->getDetails('cz', '1_2_3');
		Assert::same(['status' => 500, 'statusMessage' => 'Aww crap'], $details->jsonSerialize());
		Assert::same([$e], $this->logger->getLogged());

		$this->companyInfo->getDetails('cz', '1_2_3');
		Assert::same(['1_2_3', '1_2_3'], $this->registerCz->getCompanyIds(), 'An error is not cached');
	}


	public function testGetDetailsCachedSomethingElse(): void
	{
		(new Cache($this->cacheStorage, CompanyInfo::class))->save('cz/123', 'foo');
		$details = $this->companyInfo->getDetails('cz', '123');
		Assert::same(['status' => 500, 'statusMessage' => 'Aww crap'], $details->jsonSerialize());
		Assert::same([], $this->registerCz->getCompanyIds());
		$logged = $this->logger->getLogged();
		Assert::count(1, $logged);
		if (!$logged[0] instanceof CompanyInfoException) {
			Assert::fail('Exception is of a wrong type ' . get_debug_type($logged[0]));
		} else {
			Assert::same("Cached data for cz/123 is a 'string' not a '" . CompanyInfoDetails::class . "' object", $logged[0]->getMessage());
		}
	}

}

TestCaseRunner::run(CompanyInfoTest::class);
