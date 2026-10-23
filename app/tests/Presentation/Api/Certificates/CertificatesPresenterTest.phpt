<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\Presentation\Api\Certificates;

use MichalSpacekCz\Test\Application\ApplicationPresenter;
use MichalSpacekCz\Test\Database\Database;
use MichalSpacekCz\Test\Http\Request as HttpRequestMock;
use MichalSpacekCz\Test\Http\Response as HttpResponseMock;
use MichalSpacekCz\Test\NullLogger;
use MichalSpacekCz\Test\TestCaseRunner;
use Nette\Application\Request;
use Nette\Application\Responses\JsonResponse;
use Nette\Database\DriverException;
use Nette\Http\IRequest;
use Nette\Http\IResponse;
use Nette\Utils\FileSystem;
use Override;
use RuntimeException;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../../../bootstrap.php';

/**
 * The deploy hook that posts here reads only the HTTP status to tell whether the certificate was stored, so every
 * outcome has to carry the right status next to the JSON it has always carried, and a 200 has to mean stored.
 *
 * @testCase
 */
final class CertificatesPresenterTest extends TestCase
{

	public function __construct(
		private readonly ApplicationPresenter $applicationPresenter,
		private readonly HttpRequestMock $httpRequest,
		private readonly HttpResponseMock $httpResponse,
		private readonly Database $database,
		private readonly NullLogger $logger,
	) {
	}


	#[Override]
	protected function tearDown(): void
	{
		$this->httpRequest->reset();
		$this->httpResponse->reset();
		$this->database->reset();
		$this->logger->reset();
	}


	public function testWrongKeyIsForbidden(): void
	{
		$payload = $this->post(['user' => 'foo', 'key' => 'wrong', 'name' => 'example.com', 'certificate' => $this->certificate()]);
		$this->assertError(IResponse::S403_Forbidden, 'Invalid credentials', $payload);
		Assert::count(0, $this->database->getParamsArrayForQuery('INSERT INTO certificate_requests'));
	}


	public function testMissingCertificateIsABadRequest(): void
	{
		$payload = $this->post(['user' => 'foo', 'key' => 'foo', 'name' => 'example.com']);
		$this->assertError(IResponse::S400_BadRequest, 'No certificate sent', $payload);
	}


	public function testMissingNameIsABadRequest(): void
	{
		$payload = $this->post(['user' => 'foo', 'key' => 'foo', 'certificate' => $this->certificate()]);
		$this->assertError(IResponse::S400_BadRequest, 'No certificate name sent', $payload);
	}


	public function testEmptyNameIsABadRequest(): void
	{
		$payload = $this->post(['user' => 'foo', 'key' => 'foo', 'name' => '', 'certificate' => $this->certificate()]);
		$this->assertError(IResponse::S400_BadRequest, 'No certificate name sent', $payload);
		Assert::count(0, $this->database->getParamsArrayForQuery('INSERT INTO certificate_requests'));
	}


	public function testUnparsableCertificateIsABadRequest(): void
	{
		$payload = $this->post(['user' => 'foo', 'key' => 'foo', 'name' => 'example.com', 'certificate' => 'not a certificate']);
		$this->assertError(IResponse::S400_BadRequest, 'Cannot parse the certificate', $payload);
		Assert::count(1, $this->logger->getLogged());
		Assert::count(0, $this->database->getParamsArrayForQuery('INSERT INTO certificate_requests'));
	}


	public function testDatabaseDownIsServiceUnavailable(): void
	{
		$this->database->willThrow(new DriverException());
		$payload = $this->post(['user' => 'foo', 'key' => 'foo', 'name' => 'example.com', 'certificate' => $this->certificate()]);
		$this->assertError(IResponse::S503_ServiceUnavailable, 'Some certs logged to file', $payload);
	}


	public function testUnexpectedErrorIsAServerError(): void
	{
		$this->database->willThrow(new RuntimeException());
		$payload = $this->post(['user' => 'foo', 'key' => 'foo', 'name' => 'example.com', 'certificate' => $this->certificate()]);
		$this->assertError(IResponse::S500_InternalServerError, 'Certificate processing failed', $payload);
		Assert::count(1, $this->logger->getLogged());
	}


	public function testListingCarriesTheSameEnvelope(): void
	{
		$payload = $this->post(['user' => 'foo', 'key' => 'foo'], 'default');
		Assert::same(IResponse::S200_OK, $this->httpResponse->getCode());
		Assert::same(['message' => 'OK', 'certificates' => []], $payload);
	}


	public function testStoredCertificateIsOk(): void
	{
		$this->database->setDefaultInsertId('42');
		$payload = $this->post(['user' => 'foo', 'key' => 'foo', 'name' => 'example.com', 'certificate' => $this->certificate()]);
		Assert::same(IResponse::S200_OK, $this->httpResponse->getCode());
		Assert::same(['message' => 'Certificate reported successfully'], $payload);
		$requests = $this->database->getParamsArrayForQuery('INSERT INTO certificate_requests');
		Assert::count(1, $requests);
		Assert::same('example.com', $requests[0]['certificate_name']);
		Assert::count(1, $this->database->getParamsArrayForQuery('INSERT INTO certificates'));
		Assert::count(0, $this->logger->getLogged());
	}


	/**
	 * @param array<string, string> $fields
	 */
	private function post(array $fields, string $action = 'logIssued'): mixed
	{
		$this->httpRequest->setMethod(IRequest::Post);
		foreach ($fields as $key => $value) {
			$this->httpRequest->setPost($key, $value);
		}
		$presenter = $this->applicationPresenter->createUiPresenter('Api:Certificates', 'Certificates', $action);
		$presenter->autoCanonicalize = false;
		$response = $presenter->run(new Request('Api:Certificates', IRequest::Post, ['action' => $action]));
		assert($response instanceof JsonResponse);
		return $response->getPayload();
	}


	private function assertError(int $httpCode, string $message, mixed $payload): void
	{
		Assert::same($httpCode, $this->httpResponse->getCode());
		Assert::same(['message' => $message], $payload);
	}


	private function certificate(): string
	{
		return FileSystem::read(__DIR__ . '/../../../Tls/certificate.pem');
	}

}

TestCaseRunner::run(CertificatesPresenterTest::class);
