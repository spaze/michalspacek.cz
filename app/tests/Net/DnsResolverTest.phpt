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
		$records = $this->dnsResolver->getRecords('one.one.one.one', DNS_A | DNS_AAAA);
		$ips = $ipv6s = [];
		foreach ($records as $record) {
			if ($record->getType() === 'A') {
				$ips[] = $record->getIp();
			}
			if ($record->getType() === 'AAAA') {
				$ipv6s[] = $record->getIpv6();
			}
		}
		Assert::contains('1.0.0.1', $ips);
		Assert::contains('1.1.1.1', $ips);
		Assert::contains('2606:4700:4700::1001', $ipv6s);
		Assert::contains('2606:4700:4700::1111', $ipv6s);
	}

}

TestCaseRunner::run(DnsResolverTest::class);
