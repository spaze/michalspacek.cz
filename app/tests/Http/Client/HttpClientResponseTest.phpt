<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Http\Client;

use MichalSpacekCz\Http\Exceptions\HttpClientTlsCertificateNotAvailableException;
use MichalSpacekCz\Http\Exceptions\HttpClientTlsCertificateNotCapturedException;
use MichalSpacekCz\Test\TestCaseRunner;
use OpenSSLCertificate;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../../bootstrap.php';

/** @testCase */
final class HttpClientResponseTest extends TestCase
{

	public function testGetTlsCertificate(): void
	{
		$string = file_get_contents(__DIR__ . '/../../Tls/certificate.pem');
		assert(is_string($string));
		$certificate = openssl_x509_read($string);
		assert($certificate instanceof OpenSSLCertificate);

		Assert::exception(function () use (&$certificate): void {
			new HttpClientResponse(new HttpClientRequest(':-/'), 'body', $certificate)->getTlsCertificate();
		}, HttpClientTlsCertificateNotAvailableException::class);

		Assert::exception(function () use (&$certificate): void {
			new HttpClientResponse(new HttpClientRequest('http://not.secure.example'), 'body', $certificate)->getTlsCertificate();
		}, HttpClientTlsCertificateNotAvailableException::class);

		Assert::exception(function () use (&$certificate): void {
			new HttpClientResponse(new HttpClientRequest('https://www.example'), 'body', $certificate)->getTlsCertificate();
		}, HttpClientTlsCertificateNotCapturedException::class);

		Assert::exception(function (): void {
			new HttpClientResponse(new HttpClientRequest('https://www.example')->setTlsCaptureCertificate(true), 'body', null)->getTlsCertificate();
		}, HttpClientTlsCertificateNotCapturedException::class);

		Assert::type(OpenSSLCertificate::class, new HttpClientResponse(new HttpClientRequest('https://www.example')->setTlsCaptureCertificate(true), 'body', $certificate)->getTlsCertificate());
	}


	public function testGetBody(): void
	{
		$body = 'a response body';
		Assert::same($body, new HttpClientResponse(new HttpClientRequest('https://www.example'), $body, null)->getBody());
	}

}

TestCaseRunner::run(HttpClientResponseTest::class);
