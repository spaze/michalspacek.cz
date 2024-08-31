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
		assert(is_int($row->id));
		assert(is_string($row->name));
		assert($row->nameExtended === null || is_string($row->nameExtended));
		assert(is_string($row->href));
		assert(is_string($row->address));
		assert(is_string($row->city));
		assert($row->descriptionTexy === null || is_string($row->descriptionTexy));
		assert($row->action === null || is_string($row->action));
		assert($row->entrance === null || is_string($row->entrance));
		assert($row->entranceNavigation === null || is_string($row->entranceNavigation));
		assert($row->streetview === null || is_string($row->streetview));
		assert($row->parkingTexy === null || is_string($row->parkingTexy));
		assert($row->publicTransportTexy === null || is_string($row->publicTransportTexy));

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
