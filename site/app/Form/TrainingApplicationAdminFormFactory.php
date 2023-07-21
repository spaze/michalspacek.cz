<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Form;

use MichalSpacekCz\Form\Controls\TrainingControlsFactory;
use MichalSpacekCz\Training\Applications\TrainingApplication;
use MichalSpacekCz\Training\Applications\TrainingApplicationStorage;
use MichalSpacekCz\Training\Dates\TrainingDates;
use MichalSpacekCz\Training\Dates\UpcomingTrainingDates;
use MichalSpacekCz\Training\Statuses;
use Nette\Application\UI\Form;
use Nette\Forms\Controls\Checkbox;
use Nette\Forms\Controls\SubmitButton;

class TrainingApplicationAdminFormFactory
{

	/** @var array<string, array{0:Checkbox, 1:mixed}> */
	private array $deletableFields;


	public function __construct(
		private readonly FormFactory $factory,
		private readonly TrainingApplicationStorage $trainingApplicationStorage,
		private readonly TrainingDates $trainingDates,
		private readonly UpcomingTrainingDates $upcomingTrainingDates,
		private readonly TrainingControlsFactory $trainingControlsFactory,
		private readonly Statuses $trainingStatuses,
	) {
	}


	public function create(callable $onSuccess, callable $onStatusHistoryDeleteSuccess, TrainingApplication $application): Form
	{
		$form = $this->factory->create();

		$this->trainingControlsFactory->addAttendee($form);
		$form->addCheckbox('familiar', 'Tykání:');
		$this->trainingControlsFactory->addSource($form);
		$this->trainingControlsFactory->addCompany($form);
		$this->trainingControlsFactory->addCountry($form);
		$form->getComponent('country')->setPrompt('- vyberte zemi -');
		$this->trainingControlsFactory->addNote($form);
		$this->addPaymentInfo($form);
		$form->addSubmit('submit', 'Uložit');

		$upcoming = $this->upcomingTrainingDates->getPublicUpcoming();
		$dates = [];
		if ($application->getDateId()) {
			$dates[$application->getDateId()] = $this->trainingDates->formatDateVenueForAdmin($this->trainingDates->get($application->getDateId()));
		}
		if (isset($upcoming[$application->getTrainingAction()])) {
			foreach ($upcoming[$application->getTrainingAction()]->getDates() as $date) {
				$dates[$date->getId()] = $this->trainingDates->formatDateVenueForAdmin($date);
			}
		}
		$required = (bool)$dates;
		$form->addSelect('date', 'Datum:', $dates)
			->setPrompt($dates ? false : 'Žádný vypsaný termín')
			->setHtmlAttribute('data-original-date-id', $application->getDateId())
			->setRequired($required)
			->setDisabled(!$required);

		$this->deletableFields['name'] = [$form->addCheckbox('nameSet'), $application->getName()];
		$this->deletableFields['email'] = [$form->addCheckbox('emailSet'), $application->getEmail()];
		$this->deletableFields['company'] = [$form->addCheckbox('companySet'), $application->getCompany()];
		$this->deletableFields['street'] = [$form->addCheckbox('streetSet'), $application->getStreet()];
		$this->deletableFields['city'] = [$form->addCheckbox('citySet'), $application->getCity()];
		$this->deletableFields['zip'] = [$form->addCheckbox('zipSet'), $application->getZip()];
		$this->deletableFields['country'] = [$form->addCheckbox('countrySet'), $application->getCountry()];
		$this->deletableFields['companyId'] = [$form->addCheckbox('companyIdSet'), $application->getCompanyId()];
		$this->deletableFields['companyTaxId'] = [$form->addCheckbox('companyTaxIdSet'), $application->getCompanyTaxId()];
		$this->deletableFields['note'] = [$form->addCheckbox('noteSet'), $application->getNote()];

		foreach ($this->deletableFields as $field => [$checkbox]) {
			$checkbox->setHtmlAttribute('class', 'disableInput');
			$form->getComponent($field)
				->setHtmlAttribute('class', 'transparent')
				->setRequired(false);
		}

		$containerName = 'statusHistoryDelete';
		$historyContainer = $form->addContainer($containerName);
		foreach ($this->trainingStatuses->getStatusHistory($application->getId()) as $history) {
			$historyContainer
				->addSubmit((string)$history->id)
				->setValidationScope([$form[$containerName]])
				->onClick[] = function (SubmitButton $button) use ($application, $onStatusHistoryDeleteSuccess): void {
					$this->trainingStatuses->deleteHistoryRecord($application->getId(), (int)$button->getName());
					$onStatusHistoryDeleteSuccess();
				};
		}

		$form->onSuccess[] = function (Form $form) use ($application, $onSuccess): void {
			$values = $form->getValues();
			$dateId = $values->date ?? null;
			$this->trainingApplicationStorage->updateApplicationData(
				$application->getId(),
				$values->nameSet ? $values->name : null,
				$values->emailSet ? $values->email : null,
				$values->companySet ? $values->company : null,
				$values->streetSet ? $values->street : null,
				$values->citySet ? $values->city : null,
				$values->zipSet ? $values->zip : null,
				$values->countrySet ? $values->country : null,
				$values->companyIdSet ? $values->companyId : null,
				$values->companyTaxIdSet ? $values->companyTaxId : null,
				$values->noteSet ? $values->note : null,
				$values->source,
				(is_float($values->price) ? $values->price : null),
				(trim($values->vatRate) !== '' ? $values->vatRate / 100 : null),
				(is_float($values->priceVat) ? $values->priceVat : null),
				(trim($values->discount) !== '' ? (int)$values->discount : null),
				$values->invoiceId,
				$values->paid,
				$values->familiar,
				$dateId,
			);
			$onSuccess($dateId);
		};
		$this->setApplication($form, $application);
		return $form;
	}


	private function addPaymentInfo(Form $form): void
	{
		$form->addText('price', 'Cena bez DPH:')
			->setHtmlType('number')
			->setHtmlAttribute('step', 'any')
			->addRule($form::FLOAT)
			->setHtmlAttribute('title', 'Po případné slevě');
		$form->addText('vatRate', 'DPH:')
			->setHtmlType('number');
		$form->addText('priceVat', 'Cena s DPH:')
			->setHtmlType('number')
			->setHtmlAttribute('step', 'any')
			->addRule($form::FLOAT)
			->setHtmlAttribute('title', 'Po případné slevě');
		$form->addText('discount', 'Sleva:')
			->setHtmlType('number');
		$form->addText('invoiceId', 'Faktura č.:')
			->setHtmlType('number');
		$this->trainingControlsFactory->addPaidDate($form->addText('paid', 'Zaplaceno:'), false);
	}


	private function setApplication(Form $form, TrainingApplication $application): void
	{
		$values = [
			'name' => $application->getName(),
			'email' => $application->getEmail(),
			'familiar' => $application->isFamiliar(),
			'source' => $application->getSourceAlias(),
			'company' => $application->getCompany(),
			'street' => $application->getStreet(),
			'city' => $application->getCity(),
			'zip' => $application->getZip(),
			'country' => $application->getCountry(),
			'companyId' => $application->getCompanyId(),
			'companyTaxId' => $application->getCompanyTaxId(),
			'note' => $application->getNote(),
			'price' => $application->getPrice(),
			'vatRate' => $application->getVatRate() ? $application->getVatRate() * 100 : $application->getVatRate(),
			'priceVat' => $application->getPriceVat(),
			'discount' => $application->getDiscount(),
			'invoiceId' => $application->getInvoiceId(),
			'paid' => $application->getPaid(),
			'date' => $application->getDateId(),
		];
		foreach ($this->deletableFields as $field => [$checkbox, $value]) {
			$values[$checkbox->getName()] = ($value !== null);
			$form->getComponent($field)->setHtmlAttribute('class', $value === null ? 'transparent' : null);
		}
		$form->setDefaults($values);
	}

}
