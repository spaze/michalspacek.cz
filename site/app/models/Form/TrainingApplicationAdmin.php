<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Form;

use MichalSpacekCz\Training\Applications;
use MichalSpacekCz\Training\Dates;
use Nette\ComponentModel\IContainer;
use Nette\Database\Row;
use Nette\Forms\Container;
use Nette\Localization\ITranslator;

class TrainingApplicationAdmin extends ProtectedForm
{

	/** @var Applications */
	protected $trainingApplications;

	/** @var Dates */
	protected $trainingDates;

	/** @var TrainingControlsFactory */
	private $trainingControlsFactory;

	/** @var ITranslator */
	protected $translator;

	/** @var string[] */
	private $deletableFields = [
		'name',
		'email',
		'company',
		'street',
		'city',
		'zip',
		'country',
		'companyId',
		'companyTaxId',
		'note',
	];


	public function __construct(
		IContainer $parent,
		string $name,
		Applications $trainingApplications,
		Dates $trainingDates,
		TrainingControlsFactory $trainingControlsFactory,
		ITranslator $translator
	) {
		parent::__construct($parent, $name);
		$this->trainingApplications = $trainingApplications;
		$this->trainingDates = $trainingDates;
		$this->translator = $translator;
		$this->trainingControlsFactory = $trainingControlsFactory;

		$this->trainingControlsFactory->addAttendee($this);
		$this->addCheckbox('familiar', 'Tykání:');
		$this->trainingControlsFactory->addSource($this);
		$this->trainingControlsFactory->addCompany($this);
		$this->trainingControlsFactory->addCountry($this);
		$this->getComponent('country')->setPrompt('- vyberte zemi -');
		$this->trainingControlsFactory->addNote($this);
		$this->addPaymentInfo($this);
		$this->addSubmit('submit', 'Uložit');

		foreach ($this->deletableFields as $field) {
			$this->addCheckbox("{$field}Set")->setHtmlAttribute('class', 'disableInput');
			$this->getComponent($field)
				->setHtmlAttribute('class', 'transparent')
				->setRequired(false);
		}
	}


	protected function addPaymentInfo(Container $container): void
	{
		$this->addText('price', 'Cena bez DPH:')
			->setHtmlType('number')
			->setHtmlAttribute('title', 'Po případné slevě');
		$this->addText('vatRate', 'DPH:')
			->setHtmlType('number');
		$this->addText('priceVat', 'Cena s DPH:')
			->setHtmlType('number')
			->setHtmlAttribute('step', 'any')
			->addRule(self::FLOAT)
			->setHtmlAttribute('title', 'Po případné slevě');
		$this->addText('discount', 'Sleva:')
			->setHtmlType('number');
		$this->addText('invoiceId', 'Faktura č.:')
			->setHtmlType('number');
		$this->trainingControlsFactory->addPaidDate($this, 'paid', 'Zaplaceno:', false);
	}


	/**
	 * @param Row<mixed> $application
	 * @return self
	 */
	public function setApplication(Row $application): self
	{
		$values = array(
			'name' => $application->name,
			'email' => $application->email,
			'familiar' => $application->familiar,
			'source' => $application->sourceAlias,
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
		);
		foreach ($this->deletableFields as $field) {
			$values["{$field}Set"] = ($application->$field !== null);
			$this->getComponent($field)->setHtmlAttribute('class', $application->$field === null ? 'transparent' : null);
		}
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
		}
		return $this;
	}

}
