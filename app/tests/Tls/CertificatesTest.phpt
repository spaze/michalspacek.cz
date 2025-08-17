<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\Tls;

use DateTimeImmutable;
use MichalSpacekCz\DateTime\DateTimeFormat;
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
final class CertificatesTest extends TestCase
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


	public function testLog(): void
	{
		$this->database->setDefaultInsertId('42');
		$certificate = new Certificate('foo.example', null, $this->notBefore, $this->notAfter, 0, null);
		$this->certificates->log($certificate);
		$params = $this->database->getParamsArrayForQuery('INSERT INTO certificate_requests');
		Assert::count(1, $params);
		Assert::same('foo.example', $params[0]['cn']);
		Assert::true($params[0]['success']);
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
		$certificate = new Certificate('foo.example', null, $this->notBefore, $this->notAfter, 0, null);
		Assert::exception(function () use ($certificate): void {
			$this->certificates->log($certificate);
		}, SomeCertificatesLoggedToFileException::class, 'Error logging to database, some certificates logged to file instead');
		Assert::same($exception, $this->logger->getLogged()[0]);
		$message = 'OK foo.example from ' . $this->notBefore->format(DateTimeFormat::RFC3339_MICROSECONDS) . ' to ' . $this->notAfter->format(DateTimeFormat::RFC3339_MICROSECONDS);
		Assert::same($message, $this->logger->getLogged()[1]);
	}

}

TestCaseRunner::run(CertificatesTest::class);
