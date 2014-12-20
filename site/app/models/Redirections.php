<?php
namespace MichalSpacekCz;

/**
 * Redirections model.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
class Redirections
{

	/** @var \Nette\Database\Context */
	protected $database;


	public function __construct(\Nette\Database\Context $context)
	{
		$this->database = $context;
	}


	public function getDestination(\Nette\Http\UrlScript $sourceUrl)
	{
		$destination = $this->database->fetchField('SELECT destination FROM redirections WHERE source = ?', $sourceUrl->getPath());
		if ($destination) {
			if (!parse_url($destination, PHP_URL_HOST)) {
				$destinationUrl = clone $sourceUrl;
				$destinationUrl->setPath($destination);
				$destination = $destinationUrl->getAbsoluteUrl();
			}
		}
		return $destination;
	}

}
