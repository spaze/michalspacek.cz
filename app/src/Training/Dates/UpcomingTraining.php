<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Training\Dates;

final class UpcomingTraining
{

	/** @var array<int, TrainingDate> id => date */
	private array $dates = [];


	public function __construct(
		private readonly string $action,
		private readonly string $name,
	) {
	}


	public function getAction(): string
	{
		return $this->action;
	}


	public function getName(): string
	{
		return $this->name;
	}


	public function addDate(TrainingDate $date): void
	{
		$this->dates[$date->getId()] = $date;
	}


	/**
	 * @return array<int, TrainingDate> id => date
	 */
	public function getDates(): array
	{
		return $this->dates;
	}

}
