<?php
declare(strict_types = 1);

namespace MichalSpacekCz\User\WebAuthn;

use DateTimeImmutable;
use MichalSpacekCz\Test\TestCaseRunner;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../../bootstrap.php';

/** @testCase */
final class RegisteredPasskeyTest extends TestCase
{

	public function testGetLastUsedDaysAgoNullWhenNeverUsed(): void
	{
		$passkey = new RegisteredPasskey('019e08b4-8b1e-77b7-bb24-3c8e4aee3444', 'key', new DateTimeImmutable(), null, new DateTimeImmutable());
		Assert::null($passkey->getLastUsedDaysAgo());
	}


	public function testGetLastUsedDaysAgoZeroWhenUsedToday(): void
	{
		$now = new DateTimeImmutable('2026-05-08 14:00:00');
		$passkey = new RegisteredPasskey('019e08b4-8b1e-77b7-bb24-3c8e4aee3444', 'key', new DateTimeImmutable(), $now, $now);
		Assert::same(0, $passkey->getLastUsedDaysAgo());
	}


	public function testGetLastUsedDaysAgoOneWhenUsedYesterday(): void
	{
		$lastUsed = new DateTimeImmutable('2026-05-07 14:00:00');
		$now = new DateTimeImmutable('2026-05-08 14:00:00');
		$passkey = new RegisteredPasskey('019e08b4-8b1e-77b7-bb24-3c8e4aee3444', 'key', new DateTimeImmutable(), $lastUsed, $now);
		Assert::same(1, $passkey->getLastUsedDaysAgo());
	}


	public function testGetLastUsedDaysAgoMultipleDays(): void
	{
		$lastUsed = new DateTimeImmutable('2026-05-01 14:00:00');
		$now = new DateTimeImmutable('2026-05-08 14:00:00');
		$passkey = new RegisteredPasskey('019e08b4-8b1e-77b7-bb24-3c8e4aee3444', 'key', new DateTimeImmutable(), $lastUsed, $now);
		Assert::same(7, $passkey->getLastUsedDaysAgo());
	}


	public function testGetLastUsedDaysAgoOneWhenUsedYesterdayLessThan24HoursAgo(): void
	{
		$lastUsed = new DateTimeImmutable('2026-05-07 23:30:00');
		$now = new DateTimeImmutable('2026-05-08 00:15:00');
		$passkey = new RegisteredPasskey('019e08b4-8b1e-77b7-bb24-3c8e4aee3444', 'key', new DateTimeImmutable(), $lastUsed, $now);
		Assert::same(1, $passkey->getLastUsedDaysAgo());
	}


	public function testGetLastUsedDaysAgoOnMidnight(): void
	{
		$lastUsed = new DateTimeImmutable('2026-05-07 00:00:00');
		$now = new DateTimeImmutable('2026-05-07 23:59:59');
		$passkey = new RegisteredPasskey('019e08b4-8b1e-77b7-bb24-3c8e4aee3444', 'key', new DateTimeImmutable(), $lastUsed, $now);
		Assert::same(0, $passkey->getLastUsedDaysAgo());

		$lastUsed = new DateTimeImmutable('2026-05-07 00:00:00');
		$now = new DateTimeImmutable('2026-05-08 00:00:00');
		$passkey = new RegisteredPasskey('019e08b4-8b1e-77b7-bb24-3c8e4aee3444', 'key', new DateTimeImmutable(), $lastUsed, $now);
		Assert::same(1, $passkey->getLastUsedDaysAgo());
	}


	public function testIsSignedInWithFalseByDefault(): void
	{
		$now = new DateTimeImmutable();
		$passkey = new RegisteredPasskey('019e08b4-8b1e-77b7-bb24-3c8e4aee3444', 'key', $now, $now, $now);
		Assert::false($passkey->isSignedInWith());
	}


	public function testIsSignedInWithTrue(): void
	{
		$now = new DateTimeImmutable();
		$passkey = new RegisteredPasskey('019e08b4-8b1e-77b7-bb24-3c8e4aee3444', 'key', $now, $now, $now, true);
		Assert::true($passkey->isSignedInWith());
	}


	public function testGetCreatedAt(): void
	{
		$createdAt = new DateTimeImmutable('2026-01-01 12:00:00');
		$passkey = new RegisteredPasskey('019e08b4-8b1e-77b7-bb24-3c8e4aee3444', 'key', $createdAt, null, new DateTimeImmutable());
		Assert::same($createdAt, $passkey->getCreatedAt());
	}

}

TestCaseRunner::run(RegisteredPasskeyTest::class);
