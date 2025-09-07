<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Presentation\Www\CompanyTrainings;

use Contributte\Translation\Translator;
use MichalSpacekCz\Formatter\TexyFormatter;
use MichalSpacekCz\Presentation\Www\BasePresenter;
use MichalSpacekCz\Training\Company\CompanyTrainings;
use MichalSpacekCz\Training\Discontinued\DiscontinuedTrainings;
use MichalSpacekCz\Training\Exceptions\CompanyTrainingDoesNotExistException;
use MichalSpacekCz\Training\Prices;
use MichalSpacekCz\Training\Reviews\TrainingReviews;
use MichalSpacekCz\Training\TrainingLocales;
use MichalSpacekCz\Training\Trainings\Trainings;
use Nette\Application\BadRequestException;
use Override;

final class CompanyTrainingsPresenter extends BasePresenter
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
		private readonly Translator $translator,
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

		$successorId = $training->getSuccessorId();
		if ($successorId !== null) {
			$this->redirectPermanent('this', $this->trainings->getActionById($successorId));
		}

		$price = $training->getPrice();
		$this->template->pageTitle = $this->texyFormatter->translate('messages.title.companytraining', [$training->getName()->render()]);
		$this->template->training = $training;
		$this->template->price = $this->prices->resolvePriceVat($price);
		$this->template->reviews = $this->trainingReviews->getVisibleReviews($training->getId(), 3);
		$this->discontinuedTrainings->maybeMarkAsDiscontinued($this->template, $training->getDiscontinuedId());
	}


	/**
	 * Translated locale parameters for trainings.
	 *
	 * @return array<string, array<array-key, mixed>>
	 */
	#[Override]
	protected function getLocaleLinkParams(): array
	{
		return $this->trainingLocales->getLocaleLinkParams($this->trainingAction, $this->getParameters());
	}

}
