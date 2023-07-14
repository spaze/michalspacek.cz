<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Www\Presenters;

use MichalSpacekCz\Formatter\TexyFormatter;
use MichalSpacekCz\Training\DateList\UpcomingTrainingDatesList;
use MichalSpacekCz\Training\DateList\UpcomingTrainingDatesListFactory;
use MichalSpacekCz\Training\Venues;
use Nette\Application\BadRequestException;

class VenuesPresenter extends BasePresenter
{

	private int $venueId;


	public function __construct(
		private readonly TexyFormatter $texyFormatter,
		private readonly Venues $trainingVenues,
		private readonly UpcomingTrainingDatesListFactory $upcomingTrainingDatesListFactory,
	) {
		parent::__construct();
	}


	public function actionVenue(string $name): void
	{
		$venue = $this->trainingVenues->get($name);
		if (!$venue) {
			throw new BadRequestException("Where in the world is {$name}?");
		}
		$this->venueId = $venue->id;

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
	}


	protected function createComponentUpcomingDatesList(): UpcomingTrainingDatesList
	{
		return $this->upcomingTrainingDatesListFactory->create(null, true, $this->venueId);
	}

}
