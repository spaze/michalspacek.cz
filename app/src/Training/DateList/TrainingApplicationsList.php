<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Training\DateList;

use DateTimeImmutable;
use MichalSpacekCz\Application\UiControl;
use MichalSpacekCz\Training\Dates\TrainingDate;
use MichalSpacekCz\Training\Dates\UpcomingTrainingDates;

final class TrainingApplicationsList extends UiControl
{

	/**
	 * @param list<TrainingDate> $dates
	 */
	public function __construct(
		private readonly UpcomingTrainingDates $upcomingTrainingDates,
		private readonly array $dates,
		private readonly DateListOrder $order,
		private readonly bool $pastOnly,
	) {
	}


	public function render(): void
	{
		$this->template->trainings = $this->dates;
		$this->template->now = $this->pastOnly ? null : new DateTimeImmutable();
		$this->template->upcomingIds = $this->pastOnly ? [] : $this->upcomingTrainingDates->getPublicUpcomingIds();
		$this->template->order = $this->order;
		$this->template->render(__DIR__ . '/trainingApplicationsList.latte');
	}

}
