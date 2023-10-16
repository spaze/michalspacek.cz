<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\CompanyInfo;

use MichalSpacekCz\CompanyInfo\Exceptions\CompanyNotFoundException;
use MichalSpacekCz\Http\Client\HttpClient;
use MichalSpacekCz\Test\TestCaseRunner;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../bootstrap.php';

/** @testCase */
class CompanyRegisterRegisterUzTest extends TestCase
{

	private readonly CompanyRegisterRegisterUz $registerUz;


	public function __construct()
	{
		$this->registerUz = new CompanyRegisterRegisterUz(new HttpClient());
	}


	public function testGetDetails(): void
	{
		TestCaseRunner::skip('The test uses the Internet, to not skip the test case run it with `' . TestCaseRunner::ENV_VAR . '`');
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

		Assert::exception(function (): void {
			$this->registerUz->getDetails('1337');
		}, CompanyNotFoundException::class, 'Company not found');
	}

}

TestCaseRunner::run(CompanyRegisterRegisterUzTest::class);
