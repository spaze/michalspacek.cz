<?php
namespace MichalSpacekCz\Form;

/**
 * Training application form.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
class TrainingApplicationAdmin extends TrainingForm
{

	/** @var \MichalSpacekCz\Training\Dates */
	protected $trainingDates;


	/**
	 * @param \Nette\ComponentModel\IContainer $parent
	 * @param string $name
	 * @param \MichalSpacekCz\Training\Dates $trainingDates
	 * @param \Nette\Localization\ITranslator $translator
	 */
	public function __construct(\Nette\ComponentModel\IContainer $parent, $name, \MichalSpacekCz\Training\Dates $trainingDates, \Nette\Localization\ITranslator $translator)
	{
		parent::__construct($parent, $name, $translator);
		$this->addProtection('Platnost formuláře vypršela, odešlete jej znovu');

		$this->translator = $translator;
		$this->trainingDates = $trainingDates;

		$this->addAttendee($this);
		$this->addAttributes($this);
		$this->getComponent('equipment')->caption = 'Vlastní počítač:';

		$this->addCheckbox('familiar', 'Tykání:');
		$this->addCompany($this);
		$this->addCountry($this);
		$this->addNote($this);
		$this->addPaymentInfo($this);
		$this->addSubmit('submit', 'Uložit');
	}


	protected function addPaymentInfo(\Nette\Forms\Container $container)
	{
		$this->addText('price', 'Cena bez DPH:')
			->setType('number')
			->setAttribute('title', 'Po případné slevě');
		$this->addText('vatRate', 'DPH:')
			->setType('number');
		$this->addText('priceVat', 'Cena s DPH:')
			->setType('number')
			->setAttribute('title', 'Po případné slevě');
		$this->addText('discount', 'Sleva:')
			->setType('number');
		$this->addText('invoiceId', 'Faktura č.:')
			->setType('number');
		$this->addPaidDate('paid', 'Zaplaceno:', false);
	}


	/**
	 * @param \Nette\Database\Row $application
	 */
	public function setApplication(\Nette\Database\Row $application)
	{
		$values = array(
			'name' => $application->name,
			'email' => $application->email,
			'familiar' => $application->familiar,
			'company' => $application->company,
			'street' => $application->street,
			'city' => $application->city,
			'zip' => $application->zip,
			'country' => $application->country,
			'companyId' => $application->companyId,
			'companyTaxId' => $application->companyTaxId,
			'note' => $application->note,
			'price' => $application->price,
			'vatRate' => ($application->vatRate ? $application->vatRate * 100 : $application->vatRate),
			'priceVat' => $application->priceVat,
			'discount' => $application->discount,
			'invoiceId' => $application->invoiceId,
			'paid' => $application->paid,
			'equipment' => $application->equipment,
		);
		$this->setDefaults($values);
		if (!isset($application->dateId)) {
			$dates = $this->trainingDates->getPublicUpcoming();
			$upcoming = array();
			if (isset($dates[$application->trainingAction])) {
				foreach ($dates[$application->trainingAction]->dates as $date) {
					$upcoming[$date->dateId] = $date->start;
				}
			}
			$this->addSelect('date', 'Datum:', $upcoming)
				->setPrompt($upcoming ? '- zvolte termín -' : 'Žádný vypsaný termín')
				->setDisabled(!$upcoming);
			$this->getComponent('equipment')->setRequired(false);
		}
		return $this;
	}

}
