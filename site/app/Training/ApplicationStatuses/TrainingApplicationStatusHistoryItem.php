<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Training\ApplicationStatuses;

use DateTimeImmutable;

readonly class TrainingApplicationStatusHistoryItem
{

	public function __construct(
		private int $id,
		private int $statusId,
		private TrainingApplicationStatus $status,
		private DateTimeImmutable $statusTime,
	) {
	}


	public function getId(): int
	{
		return $this->id;
	}


	public function getStatusId(): int
	{
		return $this->statusId;
	}


	public function getStatus(): TrainingApplicationStatus
	{
		return $this->status;
	}


	public function getStatusTime(): DateTimeImmutable
	{
		return $this->statusTime;
	}

}
