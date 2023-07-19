<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Form;

use MichalSpacekCz\Form\Controls\TrainingControlsFactory;
use MichalSpacekCz\Training\Applications\TrainingApplications;
use MichalSpacekCz\Training\Dates\TrainingDates;
use MichalSpacekCz\Training\Dates\UpcomingTrainingDates;
use MichalSpacekCz\Training\Statuses;
use Nette\Application\UI\Form;
use Nette\Database\Row;
use Nette\Forms\Controls\Checkbox;
use Nette\Forms\Controls\SubmitButton;

class TrainingApplicationAdminFormFactory
{

	/** @var array<string, Checkbox> */
	private array $deletableFields;


	public function __construct(
		private readonly FormFactory $factory,
		private readonly TrainingApplications $trainingApplications,
		private readonly TrainingDates $trainingDates,
		private readonly UpcomingTrainingDates $upcomingTrainingDates,
		private readonly TrainingControlsFactory $trainingControlsFactory,
		private readonly Statuses $trainingStatuses,
	) {
	}


	public function create(callable $onSuccess, callable $onStatusHistoryDeleteSuccess, Row $application): Form
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
		if ($application->dateId) {
			$dates[$application->dateId] = $this->trainingDates->formatDateVenueForAdmin($this->trainingDates->get($application->dateId));
		}
		if (isset($upcoming[$application->trainingAction])) {
			foreach ($upcoming[$application->trainingAction]->getDates() as $date) {
				$dates[$date->getId()] = $this->trainingDates->formatDateVenueForAdmin($date);
			}
		}
		$required = (bool)$dates;
		$form->addSelect('date', 'Datum:', $dates)
			->setPrompt($dates ? false : 'Žádný vypsaný termín')
			->setHtmlAttribute('data-original-date-id', $application->dateId)
			->setRequired($required)
			->setDisabled(!$required);

		$this->deletableFields['name'] = $form->addCheckbox('nameSet');
		$this->deletableFields['email'] = $form->addCheckbox('emailSet');
		$this->deletableFields['company'] = $form->addCheckbox('companySet');
		$this->deletableFields['street'] = $form->addCheckbox('streetSet');
		$this->deletableFields['city'] = $form->addCheckbox('citySet');
		$this->deletableFields['zip'] = $form->addCheckbox('zipSet');
		$this->deletableFields['country'] = $form->addCheckbox('countrySet');
		$this->deletableFields['companyId'] = $form->addCheckbox('companyIdSet');
		$this->deletableFields['companyTaxId'] = $form->addCheckbox('companyTaxIdSet');
		$this->deletableFields['note'] = $form->addCheckbox('noteSet');

		foreach ($this->deletableFields as $field => $checkbox) {
			$checkbox->setHtmlAttribute('class', 'disableInput');
			$form->getComponent($field)
				->setHtmlAttribute('class', 'transparent')
				->setRequired(false);
		}

		$containerName = 'statusHistoryDelete';
		$historyContainer = $form->addContainer($containerName);
		foreach ($this->trainingStatuses->getStatusHistory($application->applicationId) as $history) {
			$historyContainer
				->addSubmit((string)$history->id)
				->setValidationScope([$form[$containerName]])
				->onClick[] = function (SubmitButton $button) use ($application, $onStatusHistoryDeleteSuccess): void {
					$this->trainingStatuses->deleteHistoryRecord($application->applicationId, (int)$button->getName());
					$onStatusHistoryDeleteSuccess();
				};
		}

		$form->onSuccess[] = function (Form $form) use ($application, $onSuccess): void {
			$values = $form->getValues();
			$dateId = $values->date ?? null;
			$this->trainingApplications->updateApplicationData(
				$application->applicationId,
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


	/**
	 * @param Form $form
	 * @param Row<mixed> $application
	 */
	private function setApplication(Form $form, Row $application): void
	{
		$values = [
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
			'date' => $application->dateId,
		];
		foreach ($this->deletableFields as $field => $checkbox) {
			$values[$checkbox->getName()] = ($application->$field !== null);
			$form->getComponent($field)->setHtmlAttribute('class', $application->$field === null ? 'transparent' : null);
		}
		$form->setDefaults($values);
	}

}
