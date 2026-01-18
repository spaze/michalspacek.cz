<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Tls;

use DateTimeImmutable;
use MichalSpacekCz\Test\TestCaseRunner;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../bootstrap.php';

/** @testCase */
final class CertificateTest extends TestCase
{

	public function testGetMethods(): void
	{
		$certificate = new Certificate(
			'certificate_name',
			null,
			null,
			['cert.example'],
			new DateTimeImmutable('2025-09-01 00:00:00'),
			new DateTimeImmutable('2025-09-09 00:00:00'),
			null,
			new DateTimeImmutable('2025-09-02 00:00:01'),
		);
		Assert::same(8, $certificate->getValidityPeriod());
		Assert::same(6, $certificate->getExpiryDays());
		Assert::false($certificate->isExpiringSoon());
		Assert::false($certificate->isExpired());
		Assert::false($certificate->hasWarning());

		$certificate = new Certificate(
			'certificate_name',
			null,
			null,
			['cert.example'],
			new DateTimeImmutable('2025-09-01 00:00:00'),
			new DateTimeImmutable('2025-09-09 00:00:00'),
			null,
			new DateTimeImmutable('2025-09-06 00:00:01'),
		);
		Assert::same(8, $certificate->getValidityPeriod());
		Assert::same(2, $certificate->getExpiryDays());
		Assert::true($certificate->isExpiringSoon());
		Assert::false($certificate->isExpired());
		Assert::true($certificate->hasWarning());

		$certificate = new Certificate(
			'certificate_name',
			null,
			null,
			['cert.example'],
			new DateTimeImmutable('2025-09-01 00:00:00'),
			new DateTimeImmutable('2025-09-09 00:00:00'),
			null,
			new DateTimeImmutable('2025-09-10 00:00:01'),
		);
		Assert::same(8, $certificate->getValidityPeriod());
		Assert::same(1, $certificate->getExpiryDays());
		Assert::false($certificate->isExpiringSoon());
		Assert::true($certificate->isExpired());
		Assert::true($certificate->hasWarning());
	}

}

TestCaseRunner::run(CertificateTest::class);
