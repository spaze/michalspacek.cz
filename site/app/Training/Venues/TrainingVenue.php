<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Training\Venues;

use Nette\Utils\Html;

class TrainingVenue
{

	public function __construct(
		private readonly int $id,
		private readonly string $name,
		private readonly ?string $nameExtended,
		private readonly string $href,
		private readonly string $address,
		private readonly string $city,
		private readonly ?Html $description,
		private readonly ?string $descriptionTexy,
		private readonly ?string $action,
		private readonly ?string $entrance,
		private readonly ?string $entranceNavigation,
		private readonly ?string $streetview,
		private readonly ?Html $parking,
		private readonly ?string $parkingTexy,
		private readonly ?Html $publicTransport,
		private readonly ?string $publicTransportTexy,
	) {
	}


	public function getId(): int
	{
		return $this->id;
	}


	public function getName(): string
	{
		return $this->name;
	}


	public function getNameExtended(): ?string
	{
		return $this->nameExtended;
	}


	public function getHref(): string
	{
		return $this->href;
	}


	public function getAddress(): string
	{
		return $this->address;
	}


	public function getCity(): string
	{
		return $this->city;
	}


	public function getDescription(): ?Html
	{
		return $this->description;
	}


	public function getDescriptionTexy(): ?string
	{
		return $this->descriptionTexy;
	}


	public function getAction(): ?string
	{
		return $this->action;
	}


	public function getEntrance(): ?string
	{
		return $this->entrance;
	}


	public function getEntranceNavigation(): ?string
	{
		return $this->entranceNavigation;
	}


	public function getStreetview(): ?string
	{
		return $this->streetview;
	}


	public function getParking(): ?Html
	{
		return $this->parking;
	}


	public function getParkingTexy(): ?string
	{
		return $this->parkingTexy;
	}


	public function getPublicTransport(): ?Html
	{
		return $this->publicTransport;
	}


	public function getPublicTransportTexy(): ?string
	{
		return $this->publicTransportTexy;
	}

}
