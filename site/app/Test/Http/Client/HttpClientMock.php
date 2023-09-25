<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Test\Http\Client;

use MichalSpacekCz\Http\Client\HttpClient;
use MichalSpacekCz\Http\Client\HttpClientRequest;
use MichalSpacekCz\Http\Client\HttpClientResponse;

class HttpClientMock extends HttpClient
{

	private string $response = '';


	public function setResponse(string $response): void
	{
		$this->response = $response;
	}


	public function get(HttpClientRequest $request): HttpClientResponse
	{
		return new HttpClientResponse($request, $this->response, null);
	}

}
