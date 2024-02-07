<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Http;

use MichalSpacekCz\Http\Exceptions\HttpRedirectDestinationUrlMalformedException;
use MichalSpacekCz\ShouldNotHappenException;
use Nette\Database\Explorer;
use Nette\Http\UrlScript;

readonly class Redirections
{

	public function __construct(
		private Explorer $database,
	) {
	}


	public function getDestination(UrlScript $sourceUrl): ?string
	{
		$destination = $this->database->fetchField('SELECT destination FROM redirections WHERE source = ?', $sourceUrl->getPath());
		if (!$destination) {
			return null;
		} elseif (!is_string($destination)) {
			throw new ShouldNotHappenException(sprintf("Redirect destination for '%s' is a %s not a string", $sourceUrl->getPath(), get_debug_type($destination)));
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
