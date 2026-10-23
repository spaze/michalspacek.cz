<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\Tls;

use DateTimeImmutable;
use MichalSpacekCz\Test\Http\Client\HttpClientMock;
use MichalSpacekCz\Test\TestCaseRunner;
use MichalSpacekCz\Tls\Exceptions\CertificatesApiException;
use Nette\Utils\Json;
use Override;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../bootstrap.php';

/** @testCase */
final class CertificatesApiClientTest extends TestCase
{

	public function __construct(
		private readonly CertificatesApiClient $certificatesApiClient,
		private readonly HttpClientMock $httpClient,
	) {
	}


	#[Override]
	protected function tearDown(): void
	{
		$this->httpClient->reset();
	}


	public function testReturnsTheLoggedCertificates(): void
	{
		$notAfter = new DateTimeImmutable('+3 days');
		$certificate = new Certificate('example.com', null, 'example.com', ['example.com', 'www.example.com'], new DateTimeImmutable('-3 days'), $notAfter, null, new DateTimeImmutable());
		$this->httpClient->setResponse(Json::encode(['message' => 'OK', 'certificates' => [$certificate]]));

		$certificates = $this->certificatesApiClient->getLoggedCertificates();

		Assert::count(1, $certificates);
		Assert::same('example.com', $certificates[0]->getCertificateName());
		Assert::same(['example.com', 'www.example.com'], $certificates[0]->getSubjectAlternativeNames());
		Assert::same($notAfter->getTimestamp(), $certificates[0]->getNotAfter()->getTimestamp());
		Assert::same([['user', 'key']], array_map(array_keys(...), $this->httpClient->getFormData()));
	}


	/**
	 * The API answers an error with its HTTP status and says why in the JSON body, which has no certificates,
	 * so the client has to read past the status and accept that shape to report the why.
	 */
	public function testErrorAnswerIsReportedNotFailedOn(): void
	{
		$this->httpClient->setStatusCode(403);
		$this->httpClient->setResponse('{"message":"Invalid credentials"}');

		Assert::exception(function (): void {
			$this->certificatesApiClient->getLoggedCertificates();
		}, CertificatesApiException::class, 'Response from %a% not ok, HTTP 403: Invalid credentials');

		$requests = $this->httpClient->getRequests();
		Assert::count(1, $requests);
		Assert::true($requests[0]->getIgnoreHttpErrors());
	}


	/**
	 * The API and this client live in the same repository and change together, so a field the client does not
	 * know, or a message it expects on every answer, is drift to catch, not something to tolerate.
	 */
	public function testUnknownFieldIsRejected(): void
	{
		$this->httpClient->setStatusCode(503);
		$this->httpClient->setResponse('{"message":"Some certs logged to file","retryAfter":10}');

		Assert::exception(function (): void {
			$this->certificatesApiClient->getLoggedCertificates();
		}, CertificatesApiException::class, "Cannot read response from %a%, HTTP 503 (`%a%`): Unexpected item 'retryAfter'.");
	}


	public function testMissingMessageIsRejected(): void
	{
		$this->httpClient->setResponse('{"certificates":[]}');

		Assert::exception(function (): void {
			$this->certificatesApiClient->getLoggedCertificates();
		}, CertificatesApiException::class, "Cannot read response from %a%, HTTP 200 (`%a%`): The mandatory item 'message' is missing.");
	}


	/**
	 * A web server error page is not the API answering, the message has to say so with the status and the body.
	 */
	public function testUnreadableAnswerIsReportedWithItsBody(): void
	{
		$this->httpClient->setStatusCode(502);
		$this->httpClient->setResponse('<html>bad gateway</html>');

		Assert::exception(function (): void {
			$this->certificatesApiClient->getLoggedCertificates();
		}, CertificatesApiException::class, 'Cannot read response from %a%, HTTP 502 (`<html>bad gateway</html>`): %a%');
	}

}

TestCaseRunner::run(CertificatesApiClientTest::class);
