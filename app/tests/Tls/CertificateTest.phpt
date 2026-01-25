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
			new DateTimeImmutable('2025-09-08 23:59:59'),
			null,
			new DateTimeImmutable('2025-09-02 00:00:01'),
		);
		Assert::same(7, $certificate->getValidityPeriodDays());
		Assert::same(7 * 24 + 23, $certificate->getValidityPeriodHours());
		Assert::same(6, $certificate->getExpiryDays());
		Assert::same(7 * 24 - 1, $certificate->getExpiryHours());
		Assert::false($certificate->isExpiringSoon());
		Assert::false($certificate->isExpired());
		Assert::false($certificate->hasWarning());

		$certificate = new Certificate(
			'certificate_name',
			null,
			null,
			['cert.example'],
			new DateTimeImmutable('2025-09-01 00:00:00'),
			new DateTimeImmutable('2025-09-29 00:00:00'),
			null,
			new DateTimeImmutable('2025-09-21 00:00:01'),
		);
		Assert::same(28, $certificate->getValidityPeriodDays());
		Assert::same(28 * 24, $certificate->getValidityPeriodHours());
		Assert::same(7, $certificate->getExpiryDays());
		Assert::same(8 * 24 - 1, $certificate->getExpiryHours());
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
		Assert::same(8, $certificate->getValidityPeriodDays());
		Assert::same(8 * 24, $certificate->getValidityPeriodHours());
		Assert::same(1, $certificate->getExpiryDays());
		Assert::same(24, $certificate->getExpiryHours());
		Assert::false($certificate->isExpiringSoon());
		Assert::true($certificate->isExpired());
		Assert::true($certificate->hasWarning());

		// 6-day, 160-hour certificates
		$certificate = new Certificate(
			'certificate_name',
			null,
			null,
			['cert.example'],
			new DateTimeImmutable('2025-09-01 00:00:00'),
			new DateTimeImmutable('2025-09-07 15:59:59'),
			null,
			new DateTimeImmutable('2025-09-05 01:59:59'),
		);
		Assert::same(6, $certificate->getValidityPeriodDays());
		Assert::same(6 * 24 + 15, $certificate->getValidityPeriodHours());
		Assert::same(2, $certificate->getExpiryDays());
		Assert::same(2 * 24 + 13, $certificate->getExpiryHours());
		Assert::true($certificate->isExpiringSoon());
		Assert::false($certificate->isExpired());
		Assert::true($certificate->hasWarning());

		$certificate = new Certificate(
			'certificate_name',
			null,
			null,
			['cert.example'],
			new DateTimeImmutable('2025-09-01 00:00:00'),
			new DateTimeImmutable('2025-09-07 15:59:59'),
			null,
			new DateTimeImmutable('2025-09-05 01:59:58'),
		);
		Assert::same(6, $certificate->getValidityPeriodDays());
		Assert::same(6 * 24 + 15, $certificate->getValidityPeriodHours());
		Assert::same(2, $certificate->getExpiryDays());
		Assert::same(2 * 24 + 14, $certificate->getExpiryHours());
		Assert::false($certificate->isExpiringSoon());
		Assert::false($certificate->isExpired());
		Assert::false($certificate->hasWarning());
	}

}

TestCaseRunner::run(CertificateTest::class);
