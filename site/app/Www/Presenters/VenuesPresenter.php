<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Www\Presenters;

use MichalSpacekCz\Formatter\Texy;
use MichalSpacekCz\Training\Dates;
use MichalSpacekCz\Training\Trainings;
use MichalSpacekCz\Training\Venues;
use Nette\Application\BadRequestException;

class VenuesPresenter extends BasePresenter
{

	private Texy $texyFormatter;

	private Dates $trainingDates;

	private Venues $trainingVenues;

	private Trainings $trainings;


	public function __construct(
		Texy $texyFormatter,
		Dates $trainingDates,
		Venues $trainingVenues,
		Trainings $trainings
	) {
		$this->texyFormatter = $texyFormatter;
		$this->trainingDates = $trainingDates;
		$this->trainingVenues = $trainingVenues;
		$this->trainings = $trainings;
		parent::__construct();
	}


	public function actionVenue(string $name): void
	{
		$venue = $this->trainingVenues->get($name);
		if (!$venue) {
			throw new BadRequestException("Where in the world is {$name}?");
		}

		$this->template->pageTitle = $this->texyFormatter->translate('messages.title.venue', [$venue->name]);
		$this->template->name = $venue->name;
		$this->template->nameExtended = $venue->nameExtended;
		$this->template->href = $venue->href;
		$this->template->address = $venue->address;
		$this->template->city = $venue->city;
		$this->template->description = $venue->description;
		$this->template->action = $venue->action;
		$this->template->entrance = $venue->entrance;
		$this->template->entranceNavigation = $venue->entranceNavigation;
		$this->template->streetview = $venue->streetview;
		$this->template->parking = $venue->parking;
		$this->template->publicTransport = $venue->publicTransport;

		$trainings = array();
		foreach ($this->trainingDates->getPublicUpcoming() as $training) {
			$dates = array();
			foreach ($training->dates as $date) {
				if ($date->venueId === $venue->id) {
					$dates[] = $date;
				}
			}
			if (!empty($dates)) {
				$training->dates = $dates;
				$trainings[] = $training;
			}
		}

		$this->template->lastFreeSeats = $this->trainings->lastFreeSeatsAnyTraining($trainings);
		$this->template->upcomingTrainings = $trainings;
	}

}
