<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Http;

use MichalSpacekCz\ShouldNotHappenException;
use Nette\Database\Explorer;
use Nette\Http\UrlScript;

class Redirections
{

	public function __construct(
		private readonly Explorer $database,
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
		if (!parse_url($destination, PHP_URL_HOST)) {
			$destination = $sourceUrl->withPath($destination)->getAbsoluteUrl();
		}
		return $destination;
	}

}
