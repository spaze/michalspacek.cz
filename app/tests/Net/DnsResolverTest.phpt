<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\Net;

use MichalSpacekCz\Test\TestCaseRunner;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../bootstrap.php';

/** @testCase */
final class DnsResolverTest extends TestCase
{

	public function __construct(
		private readonly DnsResolver $dnsResolver,
	) {
	}


	public function testGetRecords(): void
	{
		TestCaseRunner::needsInternet();
		$records = $this->dnsResolver->getRecords('one.one.one.one', DNS_A);
		$ips = array_map(fn(DnsRecord $dnsRecord): ?string => $dnsRecord->getIp(), $records);
		sort($ips);
		Assert::same(['1.0.0.1', '1.1.1.1'], $ips);
	}

}

TestCaseRunner::run(DnsResolverTest::class);
