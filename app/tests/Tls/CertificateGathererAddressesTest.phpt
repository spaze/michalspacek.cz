<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\Tls;

use MichalSpacekCz\Net\DnsRecord;
use MichalSpacekCz\Net\Exceptions\DnsGetRecordException;
use MichalSpacekCz\Net\PublicIpAddressValidator;
use MichalSpacekCz\Test\Http\Client\HttpClientMock;
use MichalSpacekCz\Test\Net\DnsResolverMock;
use MichalSpacekCz\Test\TestCaseRunner;
use Nette\Utils\FileSystem;
use OpenSSLCertificate;
use Override;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../bootstrap.php';

/**
 * What the gatherer does with the addresses a name has, with the DNS answers made up here,
 * so unlike CertificateGathererTest it runs without the Internet.
 *
 * @testCase
 */
final class CertificateGathererAddressesTest extends TestCase
{

	private const string NAME = 'example.com';


	public function __construct(
		private readonly CertificateFactory $certificateFactory,
		private readonly PublicIpAddressValidator $publicIpAddressValidator,
		private readonly HttpClientMock $httpClient,
	) {
	}


	#[Override]
	protected function tearDown(): void
	{
		$this->httpClient->reset();
	}


	/**
	 * An address from DNS is not ours to trust, a name can be pointed at a host on an internal network or at this host.
	 */
	public function testAddressThatIsNotPublicGetsNoRequest(): void
	{
		foreach ([new DnsRecord(self::NAME, 'IN', 300, 'A', '169.254.169.254'), new DnsRecord(self::NAME, 'IN', 300, 'AAAA', null, 'fd00::1')] as $record) {
			Assert::exception(
				fn(): array => $this->createGatherer($record)->fetchCertificates(self::NAME, true),
				DnsGetRecordException::class,
				self::NAME . ': %a% is not a public IP address (%a%)',
			);
		}
		Assert::same([], $this->httpClient->getRequests());
	}


	public function testTheRequestGoesToTheAddressAsItWasChecked(): void
	{
		$certificate = openssl_x509_read(FileSystem::read(__DIR__ . '/certificate.pem'));
		assert($certificate instanceof OpenSSLCertificate);
		$this->httpClient->setTlsCertificate($certificate);

		$certificates = $this->createGatherer(new DnsRecord(self::NAME, 'IN', 300, 'AAAA', null, '2606:4700:0:0:0:0:0:1111'))->fetchCertificates(self::NAME, true);

		Assert::same(['[2606:4700::1111]'], array_keys($certificates));
		$requests = $this->httpClient->getRequests();
		Assert::count(1, $requests);
		Assert::same('https://[2606:4700::1111]/', $requests[0]->getUrl());
	}


	private function createGatherer(DnsRecord $record): CertificateGatherer
	{
		$dnsResolver = new DnsResolverMock();
		$dnsResolver->setRecords(self::NAME, [$record]);
		return new CertificateGatherer($this->certificateFactory, $this->httpClient, $dnsResolver, $this->publicIpAddressValidator);
	}

}

TestCaseRunner::run(CertificateGathererAddressesTest::class);
