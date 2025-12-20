<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Http;

use MichalSpacekCz\Database\TypedDatabase;
use MichalSpacekCz\Http\Exceptions\HttpRedirectDestinationUrlMalformedException;
use Nette\Http\UrlScript;
use Uri\WhatWg\InvalidUrlException;
use Uri\WhatWg\Url;

final readonly class Redirections
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
		try {
			$destinationUrl = new Url($destination, Url::parse($sourceUrl->getAbsoluteUrl()));
		} catch (InvalidUrlException $e) {
			throw new HttpRedirectDestinationUrlMalformedException($destination, $e);
		}
		return $destinationUrl->toUnicodeString();
	}

}
