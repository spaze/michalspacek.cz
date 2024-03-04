<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Http;

use MichalSpacekCz\Database\TypedDatabase;
use MichalSpacekCz\Http\Exceptions\HttpRedirectDestinationUrlMalformedException;
use Nette\Http\UrlScript;

readonly class Redirections
{

	public function __construct(
		private TypedDatabase $database,
	) {
	}


	public function getDestination(UrlScript $sourceUrl): ?string
	{
		$destination = $this->database->fetchFieldStringNullable('SELECT destination FROM redirections WHERE source = ?', $sourceUrl->getPath());
		if ($destination === null) {
			return null;
		}
		$destinationUrl = parse_url($destination);
		if ($destinationUrl === false) {
			throw new HttpRedirectDestinationUrlMalformedException($destination);
		}
		if (!isset($destinationUrl['host'])) {
			$destination = $sourceUrl->withPath($destination)->getAbsoluteUrl();
		}
		return $destination;
	}

}
