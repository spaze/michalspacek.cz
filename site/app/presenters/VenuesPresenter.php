<?php
use \MichalSpacekCz\Training;

/**
 * TrainingVenues presenter.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
class VenuesPresenter extends BasePresenter
{

	/** @var \MichalSpacekCz\Training\Dates */
	protected $trainingDates;

	/** @var \MichalSpacekCz\Training\Venues */
	protected $trainingVenues;

	/** @var \MichalSpacekCz\Embed */
	protected $embed;


	/**
	 * @param \Nette\Localization\ITranslator $translator
	 * @param \MichalSpacekCz\Training\Dates $trainingDates
	 * @param \MichalSpacekCz\Training\Venues $trainingVenues
	 * @param \MichalSpacekCz\Embed $embed
	 */
	public function __construct(
		\Nette\Localization\ITranslator $translator,
		Training\Dates $trainingDates,
		Training\Venues $trainingVenues,
		\MichalSpacekCz\Embed $embed
	)
	{
		$this->trainingDates = $trainingDates;
		$this->trainingVenues = $trainingVenues;
		$this->embed = $embed;
		parent::__construct($translator);
	}


	public function actionVenue($name)
	{
		$venue = $this->trainingVenues->get($name);
		if (!$venue) {
			throw new \Nette\Application\BadRequestException("Where in the world is {$name}?", \Nette\Http\Response::S404_NOT_FOUND);
		}

		$this->template->pageTitle = 'Školírna ' . $venue->name;
		$this->template->name = $venue->name;
		$this->template->nameExtended = $venue->nameExtended;
		$this->template->href = $venue->href;
		$this->template->address = $venue->address;
		$this->template->city = $venue->city;
		$this->template->description = $venue->description;
		$this->template->action = $venue->action;
		$this->template->entrance = $venue->entrance;
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

		$this->template->lastFreeSeats = $this->trainingDates->lastFreeSeatsAnyTraining($trainings);
		$this->template->upcomingTrainings = $trainings;
	}

}
