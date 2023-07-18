<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Www\Presenters;

use MichalSpacekCz\Formatter\TexyFormatter;
use MichalSpacekCz\Training\Company\CompanyTrainings;
use MichalSpacekCz\Training\Discontinued\DiscontinuedTrainings;
use MichalSpacekCz\Training\Exceptions\CompanyTrainingDoesNotExistException;
use MichalSpacekCz\Training\Prices;
use MichalSpacekCz\Training\Reviews\TrainingReviews;
use MichalSpacekCz\Training\TrainingLocales;
use MichalSpacekCz\Training\Trainings;
use Nette\Application\BadRequestException;

class CompanyTrainingsPresenter extends BasePresenter
{

	private ?string $trainingAction = null;


	public function __construct(
		private readonly TexyFormatter $texyFormatter,
		private readonly Trainings $trainings,
		private readonly CompanyTrainings $companyTrainings,
		private readonly DiscontinuedTrainings $discontinuedTrainings,
		private readonly TrainingLocales $trainingLocales,
		private readonly TrainingReviews $trainingReviews,
		private readonly Prices $prices,
	) {
		parent::__construct();
	}


	public function renderDefault(): void
	{
		$this->template->pageTitle = $this->translator->translate('messages.title.companytrainings');
		$this->template->trainings = $this->trainings->getNames();
		$this->template->discontinued = $this->discontinuedTrainings->getAllDiscontinued();
	}


	public function actionTraining(string $name): void
	{
		$this->trainingAction = $name;
		try {
			$training = $this->companyTrainings->getInfo($name);
		} catch (CompanyTrainingDoesNotExistException $e) {
			throw new BadRequestException("I don't do {$name} training, yet", previous: $e);
		}

		if ($training->getSuccessorId() !== null) {
			$this->redirectPermanent('this', $this->trainings->getActionById($training->getSuccessorId()));
		}

		$this->template->pageTitle = $this->texyFormatter->translate('messages.title.companytraining', [$training->getName()->render()]);
		$this->template->training = $training;
		$this->template->price = $training->getPrice() ? $this->prices->resolvePriceVat($training->getPrice()) : null;
		$this->template->reviews = $this->trainingReviews->getVisibleReviews($training->getId(), 3);
		$this->discontinuedTrainings->maybeMarkAsDiscontinued($this->template, $training->getDiscontinuedId());
	}


	/**
	 * Translated locale parameters for trainings.
	 *
	 * @return array<string, array<string, string|null>>
	 */
	protected function getLocaleLinkParams(): array
	{
		return $this->trainingLocales->getLocaleLinkParams($this->trainingAction, $this->getParameters());
	}

}
