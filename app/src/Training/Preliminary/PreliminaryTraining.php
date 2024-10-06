<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Training\Preliminary;

use MichalSpacekCz\Training\Applications\TrainingApplication;

class PreliminaryTraining
{

	/** @var list<TrainingApplication> */
	private array $applications = [];


	public function __construct(
		private readonly int $id,
		private readonly string $action,
		private readonly string $name,
	) {
	}


	public function addApplication(TrainingApplication $application): void
	{
		$this->applications[] = $application;
	}


	/**
	 * @return list<TrainingApplication>
	 */
	public function getApplications(): array
	{
		return $this->applications;
	}


	public function getId(): int
	{
		return $this->id;
	}


	public function getAction(): string
	{
		return $this->action;
	}


	public function getName(): string
	{
		return $this->name;
	}

}
