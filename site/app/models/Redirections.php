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

	/** @var \Nette\Database\Connection */
	protected $database;


	public function __construct(\Nette\Database\Connection $connection)
	{
		$this->database = $connection;
	}


	public function getDestination(\Nette\Http\UrlScript $sourceUrl)
	{
		$destination = $this->database->fetchColumn('SELECT destination FROM redirections WHERE source = ?', $sourceUrl->getPath());
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