<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Test\Http\Client;

use MichalSpacekCz\Http\Client\HttpClient;
use MichalSpacekCz\Http\Client\HttpClientRequest;
use MichalSpacekCz\Http\Client\HttpClientResponse;
use OpenSSLCertificate;
use Override;

final class HttpClientMock extends HttpClient
{

	private string $response = '';

	private int $statusCode = 200;

	private ?OpenSSLCertificate $tlsCertificate = null;

	/** @var list<HttpClientRequest> */
	private array $requests = [];

	/** @var list<array<string, string>> */
	private array $formData = [];


	public function setResponse(string $response): void
	{
		$this->response = $response;
	}


	public function setStatusCode(int $statusCode): void
	{
		$this->statusCode = $statusCode;
	}


	public function setTlsCertificate(?OpenSSLCertificate $tlsCertificate): void
	{
		$this->tlsCertificate = $tlsCertificate;
	}


	/**
	 * @return list<HttpClientRequest>
	 */
	public function getRequests(): array
	{
		return $this->requests;
	}


	/**
	 * @return list<array<string, string>>
	 */
	public function getFormData(): array
	{
		return $this->formData;
	}


	public function reset(): void
	{
		$this->response = '';
		$this->statusCode = 200;
		$this->tlsCertificate = null;
		$this->requests = [];
		$this->formData = [];
	}


	#[Override]
	public function get(HttpClientRequest $request): HttpClientResponse
	{
		return $this->respond($request);
	}


	#[Override]
	public function head(HttpClientRequest $request): HttpClientResponse
	{
		return $this->respond($request);
	}


	/**
	 * @param array<string, string> $formData
	 */
	#[Override]
	public function postForm(HttpClientRequest $request, array $formData = []): HttpClientResponse
	{
		$this->formData[] = $formData;
		return $this->respond($request);
	}


	private function respond(HttpClientRequest $request): HttpClientResponse
	{
		$this->requests[] = $request;
		return new HttpClientResponse($request, $this->response, $this->tlsCertificate, ["HTTP/1.1 {$this->statusCode}"]);
	}

}
