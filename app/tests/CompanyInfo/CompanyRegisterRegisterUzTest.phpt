<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\CompanyInfo;

use MichalSpacekCz\CompanyInfo\Exceptions\CompanyNotFoundException;
use MichalSpacekCz\Http\Client\HttpClient;
use MichalSpacekCz\Test\TestCaseRunner;
use Nette\Schema\Processor;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../bootstrap.php';

/** @testCase */
final class CompanyRegisterRegisterUzTest extends TestCase
{

	private readonly CompanyRegisterRegisterUz $registerUz;


	public function __construct(
		Processor $schemaProcessor,
	) {
		// Need a real HttpClient, not the mock one used in other tests
		$this->registerUz = new CompanyRegisterRegisterUz($schemaProcessor, new HttpClient());
	}


	public function testGetDetails(): void
	{
		TestCaseRunner::needsInternet();
		$expected = new CompanyInfoDetails(
			200,
			'OK',
			'31333532',
			'SK2020317068',
			'ESET, spol. s r.o.',
			'Einsteinova 24',
			'Bratislava - mestská časť Petržalka',
			'85101',
			'sk',
		);
		Assert::equal($expected, $this->registerUz->getDetails('31333532'));
		sleep(3);

		$expected = new CompanyInfoDetails(
			200,
			'OK',
			'31337309',
			'',
			'EPSOL s r.o.',
			'Technická 7',
			'Bratislava - mestská časť Ružinov',
			'82104',
			'sk',
		);
		Assert::equal($expected, $this->registerUz->getDetails('31337309'));
		sleep(3);

		Assert::exception(function (): void {
			$this->registerUz->getDetails('1337');
		}, CompanyNotFoundException::class, 'Company not found');
	}

}

TestCaseRunner::run(CompanyRegisterRegisterUzTest::class);
