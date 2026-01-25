<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\Tls;

use DateTime;
use DateTimeImmutable;
use MichalSpacekCz\DateTime\DateTimeFormat;
use MichalSpacekCz\DateTime\DateTimeZoneFactory;
use MichalSpacekCz\Test\Database\Database;
use MichalSpacekCz\Test\DateTime\DateTimeMachineFactory;
use MichalSpacekCz\Test\NullLogger;
use MichalSpacekCz\Test\TestCaseRunner;
use MichalSpacekCz\Tls\Exceptions\SomeCertificatesLoggedToFileException;
use Nette\Database\DriverException;
use Nette\Security\AuthenticationException;
use Nette\Security\Authenticator;
use Nette\Utils\Json;
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
		private readonly DateTimeMachineFactory $dateTimeFactory,
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
		$certificate = new Certificate('foo.example', null, 'cn.example', [], $this->notBefore, $this->notAfter, null, new DateTimeImmutable());
		$this->certificates->log($certificate);
		$params = $this->database->getParamsArrayForQuery('INSERT INTO certificate_requests');
		Assert::count(1, $params);
		Assert::same('foo.example', $params[0]['certificate_name']);
		Assert::same('cn.example', $params[0]['cn']);
		Assert::true($params[0]['success']);
		foreach ($params as $values) {
			Assert::null($values['certificate_name_ext']);
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
		$certificate = new Certificate('foo.example', null, null, null, $this->notBefore, $this->notAfter, null, new DateTimeImmutable());
		Assert::exception(function () use ($certificate): void {
			$this->certificates->log($certificate);
		}, SomeCertificatesLoggedToFileException::class, 'Error logging to database, some certificates logged to file instead');
		Assert::same($exception, $this->logger->getLogged()[0]);
		$message = 'OK foo.example from ' . $this->notBefore->format(DateTimeFormat::RFC3339_MICROSECONDS) . ' to ' . $this->notAfter->format(DateTimeFormat::RFC3339_MICROSECONDS);
		Assert::same($message, $this->logger->getLogged()[1]);
	}


	public function testAuthenticate(): void
	{
		Assert::throws(function (): void {
			$this->certificates->authenticate('invalid', 'invalid');
		}, AuthenticationException::class, 'Unknown user', Authenticator::IdentityNotFound);
		Assert::throws(function (): void {
			$this->certificates->authenticate('foo', 'invalid');
		}, AuthenticationException::class, 'Invalid key', Authenticator::InvalidCredential);
		Assert::noError(function (): void {
			$this->certificates->authenticate('foo', 'foo');
		});
	}


	public function testGetNewestAndGetNewestWithWarnings(): void
	{
		$now = new DateTimeImmutable('2025-11-29 00:00:00 UTC');
		$this->dateTimeFactory->setDateTime($now);
		$this->database->addFetchAllResult([
			[
				'certificateName' => 'cert1.name',
				'certificateNameExt' => null,
				'cn' => null,
				'san' => Json::encode(['cert1.name.example']),
				'notBefore' => new DateTime('2025-09-30 10:20:30 UTC'),
				'notBeforeTimezone' => 'UTC',
				'notAfter' => new DateTime('2025-12-30 10:20:29 UTC'),
				'notAfterTimezone' => 'UTC',
			],
			[
				'certificateName' => 'cert2 expired many days ago, hidden',
				'certificateNameExt' => null,
				'cn' => null,
				'san' => Json::encode(['cert2.name.example']),
				'notBefore' => new DateTime('2025-09-20 10:20:30 UTC'),
				'notBeforeTimezone' => 'UTC',
				'notAfter' => new DateTime('2025-10-20 10:20:29 UTC'),
				'notAfterTimezone' => 'UTC',
			],
			[
				'certificateName' => 'cert3 expires soon',
				'certificateNameExt' => null,
				'cn' => null,
				'san' => Json::encode(['cert3.name.example']),
				'notBefore' => new DateTime('2025-09-08 10:20:30 UTC'),
				'notBeforeTimezone' => 'UTC',
				'notAfter' => new DateTime('2025-12-08 10:20:29 UTC'),
				'notAfterTimezone' => 'UTC',
			],
		]);
		Assert::equal(['cert1.name', 'cert3 expires soon'], $this->getCertificateNames($this->certificates->getNewest()));
		Assert::equal(['cert3 expires soon'], $this->getCertificateNames($this->certificates->getNewestWithWarnings()));

		// Test memoization
		$this->database->willThrow(new DriverException());
		Assert::equal(['cert1.name', 'cert3 expires soon'], $this->getCertificateNames($this->certificates->getNewest()));
		Assert::equal(['cert3 expires soon'], $this->getCertificateNames($this->certificates->getNewestWithWarnings()));
	}


	/**
	 * @param list<Certificate> $certificates
	 * @return list<string>
	 */
	private function getCertificateNames(array $certificates): array
	{
		return array_map(
			fn(Certificate $certificate): string => $certificate->getCertificateName(),
			$certificates,
		);
	}

}

TestCaseRunner::run(CertificatesTest::class);
