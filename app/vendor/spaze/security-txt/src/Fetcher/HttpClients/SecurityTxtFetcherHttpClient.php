<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Fetcher\HttpClients;

use Spaze\SecurityTxt\Fetcher\SecurityTxtFetcherResponse;
use Spaze\SecurityTxt\Fetcher\SecurityTxtFetcherUrl;

interface SecurityTxtFetcherHttpClient
{

	public function getResponse(SecurityTxtFetcherUrl $url, ?string $contextHost): SecurityTxtFetcherResponse;

}
