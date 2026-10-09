<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\Tls;

use MichalSpacekCz\Test\Http\Client\HttpClientMock;
use MichalSpacekCz\Test\TestCaseRunner;
use Nette\Utils\FileSystem;
use OpenSSLCertificate;
use Override;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../bootstrap.php';

/** @testCase */
final class CertificateGathererTest extends TestCase
{

	public function __construct(
		private readonly CertificateGatherer $certificateGatherer,
		private readonly HttpClientMock $httpClient,
	) {
	}


	#[Override]
	protected function tearDown(): void
	{
		$this->httpClient->reset();
	}


	/**
	 * The monitor only wants the certificate each address presents. The response status says nothing about that, a
	 * host answering 5xx has shown its certificate before answering at all, so the requests must take any status, must
	 * not follow a redirect to some other host's certificate, and must name the host so the right certificate is served.
	 */
	public function testFetchesTheCertificateOfEveryAddressWhateverTheStatus(): void
	{
		TestCaseRunner::needsInternet();
		$certificate = openssl_x509_read(FileSystem::read(__DIR__ . '/certificate.pem'));
		assert($certificate instanceof OpenSSLCertificate);
		$this->httpClient->setTlsCertificate($certificate);

		$certificates = $this->certificateGatherer->fetchCertificates('one.one.one.one', false);

		$addresses = array_keys($certificates);
		sort($addresses);
		Assert::same(['1.0.0.1', '1.1.1.1'], $addresses);
		$requests = $this->httpClient->getRequests();
		Assert::count(2, $requests);
		foreach ($requests as $request) {
			Assert::true($request->getIgnoreHttpErrors());
			Assert::false($request->getFollowLocation());
			Assert::same('one.one.one.one', $request->getTlsServerName());
			Assert::true($request->getTlsCaptureCertificate());
			Assert::contains('Host: one.one.one.one', $request->getHeaders());
			Assert::match('https://1.%d%.%d%.%d%/', $request->getUrl());
		}
	}

}

TestCaseRunner::run(CertificateGathererTest::class);
