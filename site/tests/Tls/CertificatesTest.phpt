<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\Tls;

use DateTimeImmutable;
use MichalSpacekCz\DateTime\DateTime;
use MichalSpacekCz\DateTime\DateTimeZoneFactory;
use MichalSpacekCz\Test\Database\Database;
use MichalSpacekCz\Test\NullLogger;
use MichalSpacekCz\Test\TestCaseRunner;
use MichalSpacekCz\Tls\Exceptions\SomeCertificatesLoggedToFileException;
use Nette\Database\DriverException;
use Override;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../bootstrap.php';

/** @testCase */
class CertificatesTest extends TestCase
{

	private readonly DateTimeImmutable $notBefore;
	private readonly DateTimeImmutable $notAfter;


	public function __construct(
		private readonly Certificates $certificates,
		private readonly Database $database,
		private readonly NullLogger $logger,
		private readonly DateTimeZoneFactory $dateTimeZoneFactory,
	) {
		$this->notBefore = new DateTimeImmutable('-42 days Indian/Reunion');
		$this->notAfter = new DateTimeImmutable('+42 days Atlantic/Bermuda');
	}


	#[Override]
	protected function tearDown(): void
	{
		$this->logger->reset();
	}


	public function testLogNothing(): void
	{
		$count = $this->certificates->log([], []);
		Assert::same(['certificates' => 0, 'failures' => 0], $count);
		Assert::count(0, $this->logger->getLogged());
	}


	public function testLog(): void
	{
		$this->database->setInsertId('42');
		$certificates = [
			new Certificate('foo.example', null, $this->notBefore, $this->notAfter, 0, null),
			new Certificate('bar.example', null, $this->notBefore, $this->notAfter, 0, null),
		];
		$failures = [
			new CertificateAttempt('fail.test', null),
			new CertificateAttempt('bier.test', null),
		];
		$count = $this->certificates->log($certificates, $failures);
		Assert::same(['certificates' => 2, 'failures' => 2], $count);
		$params = $this->database->getParamsArrayForQuery('INSERT INTO certificate_requests');
		Assert::count(4, $params);
		Assert::same('foo.example', $params[0]['cn']);
		Assert::true($params[0]['success']);
		Assert::same('bar.example', $params[1]['cn']);
		Assert::true($params[1]['success']);
		Assert::same('fail.test', $params[2]['cn']);
		Assert::false($params[2]['success']);
		Assert::same('bier.test', $params[3]['cn']);
		Assert::false($params[3]['success']);
		foreach ($params as $values) {
			Assert::null($values['ext']);
			Assert::hasKey('time', $values);
			Assert::hasKey('time_timezone', $values);
		}

		/** @var list<array{key_certificate_request:int, not_before:string, not_before_timezone:string, not_after:string, not_after_timezone:string}> $params */
		$params = $this->database->getParamsArrayForQuery('INSERT INTO certificates');
		Assert::same(42, $params[0]['key_certificate_request']);
		Assert::same($this->notBefore->getTimestamp(), (new DateTimeImmutable($params[0]['not_before'], $this->dateTimeZoneFactory->get($params[0]['not_before_timezone'])))->getTimestamp());
		Assert::same($this->notAfter->getTimestamp(), (new DateTimeImmutable($params[0]['not_after'], $this->dateTimeZoneFactory->get($params[0]['not_after_timezone'])))->getTimestamp());

		Assert::count(0, $this->logger->getLogged());
	}


	public function testLogDbErrors(): void
	{
		$exception = new DriverException();
		$this->database->willThrow($exception);
		$certificates = [
			new Certificate('foo.example', null, $this->notBefore, $this->notAfter, 0, null),
		];
		Assert::exception(function () use ($certificates): void {
			$this->certificates->log($certificates, []);
		}, SomeCertificatesLoggedToFileException::class, 'Error logging to database, some certificates logged to file instead');
		Assert::same($exception, $this->logger->getLogged()[0]);
		$message = 'OK foo.example from ' . $this->notBefore->format(DateTime::DATE_RFC3339_MICROSECONDS) . ' to ' . $this->notAfter->format(DateTime::DATE_RFC3339_MICROSECONDS);
		Assert::same($message, $this->logger->getLogged()[1]);
	}

}

TestCaseRunner::run(CertificatesTest::class);
