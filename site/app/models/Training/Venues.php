<?php
namespace MichalSpacekCz\Training;

/**
 * Training venues model.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
class Venues
{

	/** @var \Nette\Database\Context */
	protected $database;

	/** @var \Netxten\Formatter\Texy */
	protected $texyFormatter;


	public function __construct(\Nette\Database\Context $context, \Netxten\Formatter\Texy $texyFormatter)
	{
		$this->database = $context;
		$this->texyFormatter = $texyFormatter;
	}


	public function get($venueName)
	{
		$result = $this->database->fetch(
			'SELECT
				v.id_venue AS id,
				v.name,
				v.name_extended AS nameExtended,
				v.href,
				v.address,
				v.city,
				v.description,
				v.action,
				v.entrance,
				v.streetview,
				v.parking,
				v.public_transport AS publicTransport
			FROM training_venues v
			WHERE
				v.action = ?',
			$venueName
		);

		if ($result) {
			$result->description   = $this->texyFormatter->format($result->description);
			$result->parking = $this->texyFormatter->format($result->parking);
			$result->publicTransport = $this->texyFormatter->format($result->publicTransport);
		}

		return $result;
	}


	public function getAll()
	{
		$result = $this->database->fetchAll(
			'SELECT
				v.id_venue AS id,
				v.name
			FROM training_venues v
			ORDER BY
				v.id_venue'
		);
		return $result;
	}

}
