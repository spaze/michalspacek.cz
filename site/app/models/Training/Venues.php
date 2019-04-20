<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Training;

use Nette\Database\Context;
use Nette\Database\Row;
use Netxten\Formatter\Texy;

class Venues
{

	/** @var Context */
	protected $database;

	/** @var Texy */
	protected $texyFormatter;


	public function __construct(Context $context, Texy $texyFormatter)
	{
		$this->database = $context;
		$this->texyFormatter = $texyFormatter;
	}


	public function get(string $venueName): ?Row
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
				v.entrance_navigation AS entranceNavigation,
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


	/**
	 * @return Row[]
	 */
	public function getAll(): array
	{
		$result = $this->database->fetchAll(
			'SELECT
				v.id_venue AS id,
				v.name
			FROM training_venues v
			ORDER BY
				v.order IS NULL, v.order, v.id_venue'
		);
		return $result;
	}

}
