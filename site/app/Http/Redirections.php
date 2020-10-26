<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Http;

use Nette\Database\Context;
use Nette\Http\UrlScript;

class Redirections
{

	/** @var Context */
	protected $database;


	public function __construct(Context $context)
	{
		$this->database = $context;
	}


	/**
	 * Get destination.
	 *
	 * @param UrlScript $sourceUrl
	 * @return string|null
	 */
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
