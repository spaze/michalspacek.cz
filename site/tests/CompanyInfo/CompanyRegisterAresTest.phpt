<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\CompanyInfo;

use MichalSpacekCz\CompanyInfo\Exceptions\CompanyNotFoundException;
use MichalSpacekCz\Test\TestCaseRunner;
use Tester\Assert;
use Tester\Environment;
use Tester\TestCase;

require __DIR__ . '/../bootstrap.php';

/** @testCase */
class CompanyRegisterAresTest extends TestCase
{

	public function __construct(
		private readonly CompanyRegisterAres $ares,
	) {
	}


	public function testGetDetails(): void
	{
		if (getenv(Environment::VariableRunner)) {
			$file = basename(__FILE__);
			Environment::skip("The test uses the Internet, to not skip the test run it with `php {$file}`");
		}
		$expected = new CompanyInfoDetails(
			200,
			'OK',
			'26688093',
			'CZ26688093',
			'Landis+Gyr s.r.o.',
			'Plzeňská 3185/5a',
			'Praha',
			'15000',
			'cz',
		);
		Assert::equal($expected, $this->ares->getDetails('26688093'), 'All house number & street number & extra letter');
		sleep(3);

		$expected = new CompanyInfoDetails(
			200,
			'OK',
			'44741561',
			'CZ44741561',
			'VSACAN TOUR, s.r.o.',
			'Dolní náměstí 344',
			'Vsetín',
			'75501',
			'cz',
		);
		Assert::equal($expected, $this->ares->getDetails('44741561'), 'Just house number');
		sleep(3);

		$expected = new CompanyInfoDetails(
			200,
			'OK',
			'00256081',
			'CZ00256081',
			'Obec Srní',
			'Srní 113',
			'Srní',
			'34192',
			'cz',
		);
		Assert::equal($expected, $this->ares->getDetails('00256081'), 'No street');
		sleep(3);

		$expected = new CompanyInfoDetails(
			200,
			'OK',
			'12466743',
			'',
			'JUDr. Ivo Javůrek',
			'Jeřabinová 874/12',
			'Plzeň',
			'32600',
			'cz',
		);
		Assert::equal($expected, $this->ares->getDetails('12466743'), 'No tax id');
		sleep(3);

		Assert::exception(function (): void {
			$this->ares->getDetails('1337');
		}, CompanyNotFoundException::class, 'Invalid status 400');
		sleep(3);

		Assert::exception(function (): void {
			$this->ares->getDetails('13371338');
		}, CompanyNotFoundException::class, 'Company not found');
	}

}

TestCaseRunner::run(CompanyRegisterAresTest::class);
