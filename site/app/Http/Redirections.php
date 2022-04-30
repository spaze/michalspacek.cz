<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Http;

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
		$destination = $this->database->fetchField('SELECT destination FROM redirections WHERE source = ?', $sourceUrl->getPath()) ?: null;
		if ($destination) {
			if (!parse_url($destination, PHP_URL_HOST)) {
				$destination = $sourceUrl->withPath($destination)->getAbsoluteUrl();
			}
		}
		return $destination;
	}

}
