<?php
/** @testCase */
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\CompanyInfo;

use MichalSpacekCz\CompanyInfo\Exceptions\CompanyInfoException;
use MichalSpacekCz\CompanyInfo\Exceptions\CompanyNotFoundException;
use MichalSpacekCz\Http\Client\HttpClient;
use MichalSpacekCz\Test\Http\Client\HttpClientMock;
use MichalSpacekCz\Test\TestCaseRunner;
use Nette\Schema\Processor;
use Nette\Utils\FileSystem;
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


	public function testGetDetailsWrongIds(): void
	{
		Assert::exception(function (): void {
			$this->ares->getDetails('');
		}, CompanyInfoException::class, 'Company Id is empty');
		Assert::exception(function (): void {
			$this->ares->getDetails('foo_bar');
		}, CompanyInfoException::class, 'Company Id is not alphanumeric');
		Assert::exception(function (): void {
			$this->ares->getDetails('foo?bar=baz');
		}, CompanyInfoException::class, 'Company Id is not alphanumeric');
		Assert::exception(function (): void {
			$this->ares->getDetails('foo&bar=baz');
		}, CompanyInfoException::class, 'Company Id is not alphanumeric');
		Assert::exception(function (): void {
			$this->ares->getDetails('foo#bar');
		}, CompanyInfoException::class, 'Company Id is not alphanumeric');
	}


	public function testGetDetails(): void
	{
		TestCaseRunner::needsInternet();
		$expected = new CompanyInfoDetails(
			200,
			'OK',
			'26435675',
			'CZ26435675',
			'CineStar s.r.o.',
			'Radlická 3185/1c',
			'Praha',
			'15000',
			'cz',
		);
		Assert::equal($expected, $this->ares->getDetails('26435675'), 'All house number & street number & extra letter');
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
		$this->httpClientMock->setResponse(FileSystem::read(__DIR__ . '/ares26435675.json'));
		$expected = new CompanyInfoDetails(
			200,
			'OK',
			'26435675',
			'CZ26435675',
			'CineStar s.r.o.',
			'Radlická 3185/1c',
			'Praha',
			'15000',
			'cz',
		);
		Assert::equal($expected, $this->aresWithHttpClientMock->getDetails('26435675'));
	}

}

TestCaseRunner::run(CompanyRegisterAresTest::class);
