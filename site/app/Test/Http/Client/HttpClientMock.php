<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Test\Http\Client;

use MichalSpacekCz\Http\Client\HttpClient;
use MichalSpacekCz\Http\Client\HttpClientRequest;

class HttpClientMock extends HttpClient
{

	private string $getResult = '';


	public function setGetResult(string $getResult): void
	{
		$this->getResult = $getResult;
	}


	public function get(HttpClientRequest $request): string
	{
		return $this->getResult;
	}

}
