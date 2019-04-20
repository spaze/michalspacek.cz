<?php
declare(strict_types = 1);

namespace MichalSpacekCz;

class Redirections
{

	/** @var \Nette\Database\Context */
	protected $database;


	public function __construct(\Nette\Database\Context $context)
	{
		$this->database = $context;
	}


	/**
	 * Get destination.
	 *
	 * @param \Nette\Http\UrlScript $sourceUrl
	 * @return string|null
	 */
	public function getDestination(\Nette\Http\UrlScript $sourceUrl): ?string
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
