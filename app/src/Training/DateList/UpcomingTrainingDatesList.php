<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Training\DateList;

use Countable;
use MichalSpacekCz\Application\UiControl;
use MichalSpacekCz\Training\Dates\UpcomingTraining;
use MichalSpacekCz\Training\Dates\UpcomingTrainingDates;
use MichalSpacekCz\Training\FreeSeats;
use Override;

final class UpcomingTrainingDatesList extends UiControl implements Countable
{

	/** @var array<string, UpcomingTraining>|null */
	private ?array $upcomingTrainings = null;


	public function __construct(
		private readonly UpcomingTrainingDates $upcomingTrainingDates,
		private readonly FreeSeats $freeSeats,
		private readonly ?string $excludeTraining,
		private readonly bool $showLastFreeSeats,
		private readonly ?int $venueId,
	) {
	}


	public function render(): void
	{
		$upcomingTrainings = $this->getUpcomingTrainingDates();
		$this->template->lastFreeSeats = $this->showLastFreeSeats && $this->freeSeats->lastFreeSeatsAnyTraining($upcomingTrainings);
		$this->template->upcomingTrainings = $upcomingTrainings;
		$this->template->render(__DIR__ . '/upcomingTrainingDatesList.latte');
	}


	/**
	 * @return array<string, UpcomingTraining>
	 */
	private function getUpcomingTrainingDates(): array
	{
		if ($this->upcomingTrainings === null) {
			$this->upcomingTrainings = $this->venueId !== null ? $this->upcomingTrainingDates->getPublicUpcomingAtVenue($this->venueId) : $this->upcomingTrainingDates->getPublicUpcoming();
			if ($this->excludeTraining !== null) {
				unset($this->upcomingTrainings[$this->excludeTraining]);
			}
		}
		return $this->upcomingTrainings;
	}


	#[Override]
	public function count(): int
	{
		return count($this->getUpcomingTrainingDates());
	}

}
