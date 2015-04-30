<?php
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

	/** @var \MichalSpacekCz\Vat */
	protected $vat;


	/**
	 * @param \Nette\Localization\ITranslator $translator
	 * @param \MichalSpacekCz\Formatter\Texy $texyFormatter
	 * @param \MichalSpacekCz\Training\Trainings $trainings
	 * @param \MichalSpacekCz\Vat $vat
	 */
	public function __construct(
		Nette\Localization\ITranslator $translator,
		MichalSpacekCz\Formatter\Texy $texyFormatter,
		MichalSpacekCz\Training\Trainings $trainings,
		MichalSpacekCz\Vat $vat
	)
	{
		$this->texyFormatter = $texyFormatter;
		$this->trainings = $trainings;
		$this->vat = $vat;
		parent::__construct($translator);
	}


	public function renderDefault()
	{
		$this->template->pageTitle = $this->translator->translate('messages.title.companytrainings');
		$this->template->trainings = $this->trainings->getNames();
	}


	public function actionTraining($name)
	{
		$training = $this->trainings->get($name);
		if (!$training) {
			throw new Nette\Application\BadRequestException("I don't do {$name} training, yet", Nette\Http\Response::S404_NOT_FOUND);
		}
		$this->template->name = $training->action;
		$this->template->pageTitle = $this->texyFormatter->translate('messages.title.companytraining', [$training->name]);
		$this->template->title = $training->name;
		$this->template->descriptionCompany = $training->descriptionCompany;
		$this->template->content = $training->content;
		$this->template->upsellCompany = $training->upsellCompany;
		$this->template->prerequisites = $training->prerequisites;
		$this->template->audience = $training->audience;
		$this->template->priceCompany = $training->priceCompany;
		$this->template->priceCompanyVat = $this->vat->addVat($training->priceCompany);
		$this->template->materials = $training->materials;
		$this->template->reviews = $this->trainings->getReviews($name, 3);
	}


}
