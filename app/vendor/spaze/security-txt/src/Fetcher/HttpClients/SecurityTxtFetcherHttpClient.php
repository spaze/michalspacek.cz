<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Fetcher\HttpClients;

use Spaze\SecurityTxt\Fetcher\SecurityTxtFetcherResponse;
use Spaze\SecurityTxt\Fetcher\SecurityTxtFetcherUrl;
use Spaze\SecurityTxt\Fetcher\SecurityTxtIpAddressType;

interface SecurityTxtFetcherHttpClient
{

	public function getResponse(SecurityTxtFetcherUrl $url, string $host, string $ipAddress, SecurityTxtIpAddressType $ipAddressType): SecurityTxtFetcherResponse;

}
