<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Www\Presenters;

use MichalSpacekCz\Formatter\TexyFormatter;
use MichalSpacekCz\Training\CompanyTrainings;
use MichalSpacekCz\Training\Locales;
use MichalSpacekCz\Training\Prices;
use MichalSpacekCz\Training\Reviews;
use MichalSpacekCz\Training\Trainings;
use Nette\Application\BadRequestException;
use Nette\Http\IResponse;

class CompanyTrainingsPresenter extends BasePresenter
{

	private ?string $trainingAction = null;


	public function __construct(
		private readonly TexyFormatter $texyFormatter,
		private readonly Trainings $trainings,
		private readonly CompanyTrainings $companyTrainings,
		private readonly Locales $trainingLocales,
		private readonly Reviews $trainingReviews,
		private readonly Prices $prices,
		private readonly IResponse $httpResponse,
	) {
		parent::__construct();
	}


	public function renderDefault(): void
	{
		$this->template->pageTitle = $this->translator->translate('messages.title.companytrainings');
		$this->template->trainings = $this->trainings->getNames();
		$this->template->discontinued = $this->trainings->getAllDiscontinued();
	}


	public function actionTraining(string $name): void
	{
		$this->trainingAction = $name;
		$training = $this->companyTrainings->getInfo($name);
		if (!$training) {
			throw new BadRequestException("I don't do {$name} training, yet");
		}

		if ($training->successorId !== null) {
			$this->redirectPermanent('this', $this->trainings->getActionById($training->successorId));
		}

		$price = $this->prices->resolvePriceVat($training->price);

		$this->template->name = $training->action;
		$this->template->pageTitle = $this->texyFormatter->translate('messages.title.companytraining', [$training->name]);
		$this->template->title = $training->name;
		$this->template->description = $training->description;
		$this->template->content = $training->content;
		$this->template->upsell = $training->upsell;
		$this->template->prerequisites = $training->prerequisites;
		$this->template->audience = $training->audience;
		$this->template->duration = $training->duration;
		$this->template->alternativeDuration = $training->alternativeDuration;
		$this->template->priceWithCurrency = $price->getPriceWithCurrency();
		$this->template->priceVatWithCurrency = $price->getPriceVatWithCurrency();
		$this->template->alternativeDurationPriceText = $training->alternativeDurationPriceText;
		$this->template->materials = $training->materials;
		$this->template->reviews = $this->trainingReviews->getVisibleReviews($training->trainingId, 3);
		if ($training->discontinuedId !== null) {
			$this->template->discontinued = [$this->trainings->getDiscontinued($training->discontinuedId)];
			$this->httpResponse->setCode(IResponse::S410_Gone);
		} else {
			$this->template->discontinued = null;
		}
	}


	/**
	 * Translated locale parameters for trainings.
	 *
	 * @return array<string, array<string, string|null>>
	 */
	protected function getLocaleLinkParams(): array
	{
		if (!$this->trainingAction) {
			return parent::getLocaleLinkParams();
		} else {
			$params = [];
			foreach ($this->trainingLocales->getLocaleActions($this->trainingAction) as $key => $value) {
				$params[$key] = ['name' => $value];
			}
			return $params;
		}
	}

}
