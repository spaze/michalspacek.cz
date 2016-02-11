<?php
namespace App\Presenters;

/**
 * Company Trainings presenter.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
class CompanyTrainingsPresenter extends BasePresenter
{

	/** @var \MichalSpacekCz\Formatter\Texy */
	protected $texyFormatter;

	/** @var \MichalSpacekCz\Training\Trainings */
	protected $trainings;

	/** @var \MichalSpacekCz\Training\CompanyTrainings */
	protected $companyTrainings;

	/** @var \MichalSpacekCz\Vat */
	protected $vat;


	/**
	 * @param \MichalSpacekCz\Formatter\Texy $texyFormatter
	 * @param \MichalSpacekCz\Training\Trainings $trainings
	 * @param \MichalSpacekCz\Training\CompanyTrainings $companyTrainings
	 * @param \MichalSpacekCz\Vat $vat
	 */
	public function __construct(
		\MichalSpacekCz\Formatter\Texy $texyFormatter,
		\MichalSpacekCz\Training\Trainings $trainings,
		\MichalSpacekCz\Training\CompanyTrainings $companyTrainings,
		\MichalSpacekCz\Vat $vat
	)
	{
		$this->texyFormatter = $texyFormatter;
		$this->trainings = $trainings;
		$this->companyTrainings = $companyTrainings;
		$this->vat = $vat;
	}


	public function renderDefault()
	{
		$this->template->pageTitle = $this->translator->translate('messages.title.companytrainings');
		$this->template->trainings = $this->trainings->getNames();
	}


	public function actionTraining($name)
	{
		$training = $this->companyTrainings->getInfo($name);
		if (!$training) {
			throw new \Nette\Application\BadRequestException("I don't do {$name} training, yet", \Nette\Http\Response::S404_NOT_FOUND);
		}
		$this->template->name = $training->action;
		$this->template->pageTitle = $this->texyFormatter->translate('messages.title.companytraining', [$training->name]);
		$this->template->title = $training->name;
		$this->template->description = $training->description;
		$this->template->content = $training->content;
		$this->template->upsell = $training->upsell;
		$this->template->prerequisites = $training->prerequisites;
		$this->template->audience = $training->audience;
		$this->template->duration = $training->duration;
		$this->template->doubleDuration = $training->doubleDuration;
		$this->template->price = $training->price;
		$this->template->priceVat = $this->vat->addVat($training->price);
		$this->template->doubleDurationPrice = $training->doubleDurationPrice;
		$this->template->materials = $training->materials;
		$this->template->reviews = $this->trainings->getReviews($name, 3);
	}


}
