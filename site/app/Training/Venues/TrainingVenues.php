<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Training\Venues;

use MichalSpacekCz\Formatter\TexyFormatter;
use MichalSpacekCz\Training\Exceptions\TrainingVenueNotFoundException;
use Nette\Database\Explorer;
use Nette\Database\Row;

readonly class TrainingVenues
{

	public function __construct(
		private Explorer $database,
		private TexyFormatter $texyFormatter,
	) {
	}


	/**
	 * @throws TrainingVenueNotFoundException
	 */
	public function get(string $venueName): TrainingVenue
	{
		$result = $this->database->fetch(
			'SELECT
				v.id_venue AS id,
				v.name,
				v.name_extended AS nameExtended,
				v.href,
				v.address,
				v.city,
				v.description AS descriptionTexy,
				v.action,
				v.entrance,
				v.entrance_navigation AS entranceNavigation,
				v.streetview,
				v.parking AS parkingTexy,
				v.public_transport AS publicTransportTexy
			FROM training_venues v
			WHERE
				v.action = ?',
			$venueName,
		);

		if (!$result) {
			throw new TrainingVenueNotFoundException($venueName);
		}
		return $this->createFromDatabaseRow($result);
	}


	/**
	 * @return list<TrainingVenue>
	 */
	public function getAll(): array
	{
		$rows = $this->database->fetchAll(
			'SELECT
				v.id_venue AS id,
				v.name,
				v.name_extended AS nameExtended,
				v.href,
				v.address,
				v.city,
				v.description AS descriptionTexy,
				v.action,
				v.entrance,
				v.entrance_navigation AS entranceNavigation,
				v.streetview,
				v.parking AS parkingTexy,
				v.public_transport AS publicTransportTexy
			FROM training_venues v
			ORDER BY
				v.order IS NULL, v.order, v.name',
		);
		$venues = [];
		foreach ($rows as $row) {
			$venues[] = $this->createFromDatabaseRow($row);
		}
		return $venues;
	}


	private function createFromDatabaseRow(Row $row): TrainingVenue
	{
		return new TrainingVenue(
			$row->id,
			$row->name,
			$row->nameExtended,
			$row->href,
			$row->address,
			$row->city,
			$row->descriptionTexy ? $this->texyFormatter->format($row->descriptionTexy) : null,
			$row->descriptionTexy,
			$row->action,
			$row->entrance,
			$row->entranceNavigation,
			$row->streetview,
			$row->parkingTexy ? $this->texyFormatter->format($row->parkingTexy) : null,
			$row->parkingTexy,
			$row->publicTransportTexy ? $this->texyFormatter->format($row->publicTransportTexy) : null,
			$row->publicTransportTexy,
		);
	}

}
