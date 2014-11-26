<?php
/**
 * Company Trainings presenter.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
class CompanyTrainingsPresenter extends BasePresenter
{

	/** @var \MichalSpacekCz\Trainings */
	protected $trainings;

	/** @var \MichalSpacekCz\Vat */
	protected $vat;


	/**
	 * @param \Nette\Localization\ITranslator $translator
	 * @param \MichalSpacekCz\Trainings $trainings
	 * @param \MichalSpacekCz\Vat $vat
	 */
	public function __construct(
		\Nette\Localization\ITranslator $translator,
		\MichalSpacekCz\Trainings $trainings,
		\MichalSpacekCz\Vat $vat
	)
	{
		$this->trainings = $trainings;
		$this->vat = $vat;
		parent::__construct($translator);
	}


	public function renderDefault()
	{
		$this->template->pageTitle = 'Školení pro firmy';
		$this->template->trainings = $this->trainings->getNames();
	}


	public function actionTraining($name)
	{
		$training = $this->trainings->get($name);
		if (!$training) {
			throw new \Nette\Application\BadRequestException("I don't do {$name} training, yet", Response::S404_NOT_FOUND);
		}
		$this->template->name = $training->action;
		$this->template->pageTitle = 'Firemní školení ' . $training->name;
		$this->template->title = $training->name;
		$this->template->descriptionCompany = $training->descriptionCompany;
		$this->template->content = $training->content;
		$this->template->priceCompany = $training->priceCompany;
		$this->template->priceCompanyVat = $this->vat->addVat($training->priceCompany);
	}


}
