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

	private ?OpenSSLCertificate $tlsCertificate = null;

	/** @var list<HttpClientRequest> */
	private array $requests = [];


	public function setResponse(string $response): void
	{
		$this->response = $response;
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


	public function reset(): void
	{
		$this->response = '';
		$this->tlsCertificate = null;
		$this->requests = [];
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


	private function respond(HttpClientRequest $request): HttpClientResponse
	{
		$this->requests[] = $request;
		return new HttpClientResponse($request, $this->response, $this->tlsCertificate, []);
	}

}
