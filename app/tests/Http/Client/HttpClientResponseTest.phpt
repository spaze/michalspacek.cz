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
			new HttpClientResponse(new HttpClientRequest(':-/'), 'body', $certificate, [])->getTlsCertificate();
		}, HttpClientTlsCertificateNotAvailableException::class);

		Assert::exception(function () use (&$certificate): void {
			new HttpClientResponse(new HttpClientRequest('http://not.secure.example'), 'body', $certificate, [])->getTlsCertificate();
		}, HttpClientTlsCertificateNotAvailableException::class);

		Assert::exception(function () use (&$certificate): void {
			new HttpClientResponse(new HttpClientRequest('https://www.example'), 'body', $certificate, [])->getTlsCertificate();
		}, HttpClientTlsCertificateNotCapturedException::class);

		Assert::exception(function (): void {
			new HttpClientResponse(new HttpClientRequest('https://www.example')->setTlsCaptureCertificate(true), 'body', null, [])->getTlsCertificate();
		}, HttpClientTlsCertificateNotCapturedException::class);

		Assert::type(OpenSSLCertificate::class, new HttpClientResponse(new HttpClientRequest('https://www.example')->setTlsCaptureCertificate(true), 'body', $certificate, [])->getTlsCertificate());
	}


	public function testGetBody(): void
	{
		$body = 'a response body';
		Assert::same($body, new HttpClientResponse(new HttpClientRequest('https://www.example'), $body, null, [])->getBody());
	}


	public function testGetHeaderAllHeaders(): void
	{
		$headers = [
			'Single-Header:foo',
			'Single-Header2:    foo   ',
			'Multiple-Header: bar',
			'Multiple-Header: baz',
		];
		$response = new HttpClientResponse(new HttpClientRequest('https://www.example'), '', null, $headers);
		Assert::same('foo', $response->getHeader('single-header'));
		Assert::same(['foo'], $response->getAllHeaders('single-header'));
		Assert::same('foo', $response->getHeader('SiNgLe-HeAdEr2'));
		Assert::same(['foo'], $response->getAllHeaders('SiNgLe-HeAdEr2'));
		Assert::same('bar', $response->getHeader('multiple-header'));
		Assert::same(['bar', 'baz'], $response->getAllHeaders('multiple-header'));
	}


	public function testKeepsOnlyTheLastResponseAfterRedirects(): void
	{
		$headers = [
			'HTTP/1.1 301 Moved Permanently',
			'Location: https://www.example/final',
			'X-Hop: first',
			'HTTP/1.1 200 OK',
			'X-Hop: second',
		];
		$response = new HttpClientResponse(new HttpClientRequest('https://www.example'), '', null, $headers);
		Assert::same('second', $response->getHeader('x-hop')); // the final response, not the redirect
		Assert::same(['second'], $response->getAllHeaders('x-hop'));
		Assert::null($response->getHeader('location')); // the redirect hop's header is discarded
		Assert::null($response->getAllHeaders('location'));
	}

}

TestCaseRunner::run(HttpClientResponseTest::class);
