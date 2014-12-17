<?php
namespace MichalSpacekCz\Form;

/**
 * Training application form.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
class TrainingApplicationAdmin extends TrainingApplication
{

	/**
	 * @param \Nette\ComponentModel\IContainer $parent
	 * @param string $name
	 */
	public function __construct(\Nette\ComponentModel\IContainer $parent, $name)
	{
		\Nette\Application\UI\Form::__construct($parent, $name);

		$this->addAttendee($this);
		$this->addCheckbox('familiar', 'Tykání:');
		$this->addCompany($this);
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
		$this->addText('paid', 'Zaplaceno:')
			->setAttribute('placeholder', 'YYYY-MM-DD nebo YYYY-MM-DD HH:MM:SS nebo DD.MM.YYYY nebo NOW')
			->setAttribute('title', 'Formát  YYYY-MM-DD nebo YYYY-MM-DD HH:MM:SS nebo DD.MM.YYYY nebo NOW')
			->addCondition(self::FILLED)
			->addRule(self::PATTERN, 'Datum musí být ve formátu YYYY-MM-DD nebo YYYY-MM-DD HH:MM:SS nebo DD.MM.YYYY nebo NOW', '((\d{4}-\d{1,2}-\d{1,2})( \d{1,2}:\d{2}:\d{2})?)|(\d{1,2}\.\d{1,2}\.\d{4})|[Nn][Oo][Ww]');
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
			'companyId' => $application->companyId,
			'companyTaxId' => $application->companyTaxId,
			'note' => $application->note,
			'price' => $application->price,
			'vatRate' => $application->vatRate * 100,
			'priceVat' => $application->priceVat,
			'discount' => $application->discount,
			'invoiceId' => $application->invoiceId,
			'paid' => $application->paid,
		);
		$this->setDefaults($values);
		return $this;
	}

}
