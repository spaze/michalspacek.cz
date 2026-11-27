<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\Net;

use MichalSpacekCz\Net\Exceptions\IpAddressNotPublicException;
use MichalSpacekCz\Test\TestCaseRunner;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../bootstrap.php';

/** @testCase */
final class PublicIpAddressValidatorTest extends TestCase
{

	public function __construct(
		private readonly PublicIpAddressValidator $publicIpAddressValidator,
	) {
	}


	public function testPublicAddressComesBackAsIpLibWritesIt(): void
	{
		Assert::same('1.1.1.1', $this->publicIpAddressValidator->validate('1.1.1.1'));
		Assert::same('2606:4700::1111', $this->publicIpAddressValidator->validate('2606:4700::1111'));
		Assert::same('2606:4700::1111', $this->publicIpAddressValidator->validate('2606:4700:0:0:0:0:0:1111'));
		// A resolver reads the leading zero as octal and would go to 127.0.0.1, so what comes back is the address that was checked
		Assert::same('177.0.0.1', $this->publicIpAddressValidator->validate('0177.0.0.1'));
	}


	public function testAddressThatIsNotPublicIsRefused(): void
	{
		$addresses = [
			'10.0.0.1' => 'private',
			'192.168.1.1' => 'private',
			'172.16.0.1' => 'private',
			'127.0.0.1' => 'loopback',
			'169.254.169.254' => 'link-local, where cloud metadata services are',
			'100.64.0.1' => 'carrier-grade NAT',
			'0.0.0.0' => 'unspecified',
			'192.0.2.1' => 'documentation',
			'224.0.0.1' => 'multicast',
			'::1' => 'loopback',
			'fe80::1' => 'link-local',
			'fd00::1' => 'unique local',
			'fec0::1' => 'site-local',
			'ff02::1' => 'multicast',
			'::ffff:10.0.0.1' => 'a private IPv4 address mapped to IPv6',
			'2002:a00:1::1' => '6to4 of the private 10.0.0.1',
			'64:ff9b::a9fe:a9fe' => 'NAT64 of 169.254.169.254',
			'64:ff9b::101:101' => 'NAT64 of the public 1.1.1.1, refused with the rest of NAT64',
			'64:ff9b:1::a9fe:a9fe' => 'NAT64 of 169.254.169.254 with the prefix for local use',
			'2001:db8::1' => 'documentation',
			'3fff::1' => 'documentation, the newer range, which PHP takes as public',
			'2001:2::1' => 'benchmarking',
			'2001:1::1' => 'PCP anycast, which ip-lib takes as public',
			'2001:1::2' => 'TURN anycast, the same',
			'2001::1' => 'Teredo, the same',
			'2002:101:101::1' => '6to4 of the public 1.1.1.1, refused with the rest of 6to4',
			'::ffff:1.1.1.1' => 'the public 1.1.1.1 mapped to IPv6, refused with the rest of the mapped addresses',
			'127.1' => 'not written as four numbers',
			'[::1]' => 'in brackets',
			'1.1.1.1:443' => 'with a port',
			'example.com' => 'a hostname',
			'' => 'nothing',
		];
		$refused = [];
		foreach (array_keys($addresses) as $address) {
			try {
				$this->publicIpAddressValidator->validate($address);
			} catch (IpAddressNotPublicException) {
				$refused[] = $address;
			}
		}
		Assert::same(array_keys($addresses), $refused);
	}


	public function testTheRefusalSaysWhatTheAddressIs(): void
	{
		Assert::exception(
			fn(): string => $this->publicIpAddressValidator->validate('10.0.0.1'),
			IpAddressNotPublicException::class,
			'10.0.0.1 is not a public IP address (%a%)',
		);
		Assert::exception(
			fn(): string => $this->publicIpAddressValidator->validate('2001:1::1'),
			IpAddressNotPublicException::class,
			'2001:1::1 is not a public IP address (Not globally reachable)',
		);
		Assert::exception(
			fn(): string => $this->publicIpAddressValidator->validate('example.com'),
			IpAddressNotPublicException::class,
			'example.com is not an IP address',
		);
	}

}

TestCaseRunner::run(PublicIpAddressValidatorTest::class);
