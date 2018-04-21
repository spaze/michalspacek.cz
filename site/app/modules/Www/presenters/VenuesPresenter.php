<?php
namespace App\WwwModule\Presenters;

use MichalSpacekCz\Training;

/**
 * TrainingVenues presenter.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
class VenuesPresenter extends BasePresenter
{

	/** @var \MichalSpacekCz\Formatter\Texy */
	protected $texyFormatter;

	/** @var \MichalSpacekCz\Training\Dates */
	protected $trainingDates;

	/** @var \MichalSpacekCz\Training\Venues */
	protected $trainingVenues;

	/** @var \MichalSpacekCz\Training\Trainings */
	protected $trainings;

	/** @var \MichalSpacekCz\Embed */
	protected $embed;


	/**
	 * @param \MichalSpacekCz\Formatter\Texy $texyFormatter
	 * @param \MichalSpacekCz\Training\Dates $trainingDates
	 * @param \MichalSpacekCz\Training\Venues $trainingVenues
	 * @param \MichalSpacekCz\Training\Trainings $trainings
	 * @param \MichalSpacekCz\Embed $embed
	 */
	public function __construct(
		\MichalSpacekCz\Formatter\Texy $texyFormatter,
		Training\Dates $trainingDates,
		Training\Venues $trainingVenues,
		Training\Trainings $trainings,
		\MichalSpacekCz\Embed $embed
	)
	{
		$this->texyFormatter = $texyFormatter;
		$this->trainingDates = $trainingDates;
		$this->trainingVenues = $trainingVenues;
		$this->trainings = $trainings;
		$this->embed = $embed;
		parent::__construct();
	}


	public function actionVenue($name)
	{
		$venue = $this->trainingVenues->get($name);
		if (!$venue) {
			throw new \Nette\Application\BadRequestException("Where in the world is {$name}?", \Nette\Http\IResponse::S404_NOT_FOUND);
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
