<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\CompanyInfo;

use MichalSpacekCz\CompanyInfo\Exceptions\CompanyNotFoundException;
use MichalSpacekCz\Http\Client\HttpClient;
use MichalSpacekCz\Test\Http\Client\HttpClientMock;
use MichalSpacekCz\Test\TestCaseRunner;
use Nette\Schema\Processor;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../bootstrap.php';

/** @testCase */
final class CompanyRegisterAresTest extends TestCase
{

	private readonly CompanyRegisterAres $ares;


	public function __construct(
		private readonly CompanyRegisterAres $aresWithHttpClientMock,
		private readonly HttpClientMock $httpClientMock,
	) {
		$this->ares = new CompanyRegisterAres(new Processor(), new HttpClient());
	}


	public function testGetDetails(): void
	{
		TestCaseRunner::needsInternet();
		$expected = new CompanyInfoDetails(
			200,
			'OK',
			'26688093',
			'CZ26688093',
			'Landis+Gyr s.r.o.',
			'Generála Píky 430/26',
			'Praha',
			'16000',
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


	public function testGetDetailsWithHttpMock(): void
	{
		$this->httpClientMock->setResponse('{
		   "adresaDorucovaci" : {},
		   "czNace" : [
			  "46900",
			  "27120",
			  "95110",
			  "69200",
			  "620",
			  "4778"
		   ],
		   "datumAktualizace" : "2023-08-10",
		   "datumVzniku" : "2023-03-30",
		   "dic" : "CZ26688093",
		   "financniUrad" : "005",
		   "ico" : "26688093",
		   "icoId" : "26688093",
		   "obchodniJmeno" : "Landis+Gyr s.r.o.",
		   "pravniForma" : "112",
		   "primarniZdroj" : "vr",
		   "seznamRegistraci" : {
			  "stavZdrojeCeu" : "NEEXISTUJICI",
			  "stavZdrojeDph" : "AKTIVNI",
			  "stavZdrojeIr" : "NEEXISTUJICI",
			  "stavZdrojeNrpzs" : "NEEXISTUJICI",
			  "stavZdrojeRcns" : "NEEXISTUJICI",
			  "stavZdrojeRed" : "NEEXISTUJICI",
			  "stavZdrojeRes" : "AKTIVNI",
			  "stavZdrojeRpsh" : "NEEXISTUJICI",
			  "stavZdrojeRs" : "NEEXISTUJICI",
			  "stavZdrojeRzp" : "AKTIVNI",
			  "stavZdrojeSzr" : "NEEXISTUJICI",
			  "stavZdrojeVr" : "AKTIVNI"
		   },
		   "sidlo" : {
			  "cisloDomovni" : 3185,
			  "cisloOrientacni" : 5,
			  "cisloOrientacniPismeno" : "a",
			  "kodAdresnihoMista" : 25713787,
			  "kodCastiObce" : 400301,
			  "kodKraje" : 19,
			  "kodMestskeCastiObvodu" : 500143,
			  "kodMestskehoObvodu" : 51,
			  "kodObce" : 554782,
			  "kodOkresu" : 3100,
			  "kodStatu" : "CZ",
			  "kodUlice" : 464287,
			  "nazevCastiObce" : "Smíchov",
			  "nazevKraje" : "Hlavní město Praha",
			  "nazevMestskeCastiObvodu" : "Praha 5",
			  "nazevMestskehoObvodu" : "Praha 5",
			  "nazevObce" : "Praha",
			  "nazevStatu" : "Česká republika",
			  "nazevUlice" : "Plzeňská",
			  "psc" : 15000,
			  "textovaAdresa" : "Plzeňská 3185/5a, Smíchov, 15000 Praha 5"
		   }
		}');
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
		Assert::equal($expected, $this->aresWithHttpClientMock->getDetails('26688093'));
	}

}

TestCaseRunner::run(CompanyRegisterAresTest::class);
