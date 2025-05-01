<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\Net;

use MichalSpacekCz\Test\Database\Database;
use MichalSpacekCz\Test\TestCaseRunner;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../bootstrap.php';

/** @testCase */
final class IpRangesTest extends TestCase
{

	public function __construct(
		private readonly IpRanges $ipRanges,
		private readonly Database $database,
	) {
	}


	public function testGetRangeName(): void
	{
		$this->database->setFetchFieldDefaultResult('Cloudflare');
		Assert::same('Cloudflare', $this->ipRanges->getRangeName('127.0.0.1', IpAddressType::V4));
		Assert::null($this->ipRanges->getRangeName('definitely not an IP address', IpAddressType::V4));
		Assert::null($this->ipRanges->getRangeName('', IpAddressType::V4));
		Assert::null($this->ipRanges->getRangeName('1234', IpAddressType::V4));
		Assert::null($this->ipRanges->getRangeName('123.456.78.9', IpAddressType::V4));
		Assert::same('Cloudflare', $this->ipRanges->getRangeName('123.45.67.89', IpAddressType::V4));
	}

}

TestCaseRunner::run(IpRangesTest::class);
