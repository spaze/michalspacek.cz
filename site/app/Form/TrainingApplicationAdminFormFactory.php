<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Form;

use MichalSpacekCz\DateTime\Exceptions\InvalidTimezoneException;
use MichalSpacekCz\Form\Controls\TrainingControlsFactory;
use MichalSpacekCz\Training\Applications\TrainingApplication;
use MichalSpacekCz\Training\Applications\TrainingApplicationStorage;
use MichalSpacekCz\Training\Dates\TrainingDates;
use MichalSpacekCz\Training\Dates\UpcomingTrainingDates;
use MichalSpacekCz\Training\Exceptions\TrainingDateDoesNotExistException;
use MichalSpacekCz\Training\Exceptions\TrainingDateNotRemoteNoVenueException;
use MichalSpacekCz\Training\Statuses\TrainingStatusHistory;
use Nette\Forms\Controls\BaseControl;
use Nette\Forms\Controls\Checkbox;
use Nette\Forms\Controls\SubmitButton;

readonly class TrainingApplicationAdminFormFactory
{

	public function __construct(
		private FormFactory $factory,
		private TrainingApplicationStorage $trainingApplicationStorage,
		private TrainingDates $trainingDates,
		private UpcomingTrainingDates $upcomingTrainingDates,
		private TrainingControlsFactory $trainingControlsFactory,
		private TrainingStatusHistory $trainingStatusHistory,
	) {
	}


	/**
	 * @throws TrainingDateDoesNotExistException
	 * @throws TrainingDateNotRemoteNoVenueException
	 * @throws InvalidTimezoneException
	 */
	public function create(callable $onSuccess, callable $onStatusHistoryDeleteSuccess, TrainingApplication $application): UiForm
	{
		$form = $this->factory->create();

		$attendeeInputs = $this->trainingControlsFactory->addAttendee($form);
		$form->addCheckbox('familiar', 'Tykání:');
		$this->trainingControlsFactory->addSource($form);
		$companyInputs = $this->trainingControlsFactory->addCompany($form);
		$countryInput = $this->trainingControlsFactory->addCountry($form)->setPrompt('- vyberte zemi -');
		$noteInput = $this->trainingControlsFactory->addNote($form);
		$this->addPaymentInfo($form);
		$form->addSubmit('submit', 'Uložit');

		$upcoming = $this->upcomingTrainingDates->getPublicUpcoming();
		$dates = [];
		$dateId = $application->getDateId();
		if ($dateId) {
			$dates[$dateId] = $this->trainingDates->formatDateVenueForAdmin($this->trainingDates->get($dateId));
		}
		if (isset($upcoming[$application->getTrainingAction()])) {
			foreach ($upcoming[$application->getTrainingAction()]->getDates() as $date) {
				$dates[$date->getId()] = $this->trainingDates->formatDateVenueForAdmin($date);
			}
		}
		$required = (bool)$dates;
		$form->addSelect('date', 'Datum:', $dates)
			->setPrompt($dates ? false : 'Žádný vypsaný termín')
			->setHtmlAttribute('data-original-date-id', $dateId)
			->setRequired($required)
			->setDisabled(!$required);

		$this->addDeletableFieldCheckbox($attendeeInputs->getName(), $form->addCheckbox('nameSet'), $application->getName());
		$this->addDeletableFieldCheckbox($attendeeInputs->getEmail(), $form->addCheckbox('emailSet'), $application->getEmail());
		$this->addDeletableFieldCheckbox($companyInputs->getCompany(), $form->addCheckbox('companySet'), $application->getCompany());
		$this->addDeletableFieldCheckbox($companyInputs->getStreet(), $form->addCheckbox('streetSet'), $application->getStreet());
		$this->addDeletableFieldCheckbox($companyInputs->getCity(), $form->addCheckbox('citySet'), $application->getCity());
		$this->addDeletableFieldCheckbox($companyInputs->getZip(), $form->addCheckbox('zipSet'), $application->getZip());
		$this->addDeletableFieldCheckbox($countryInput, $form->addCheckbox('countrySet'), $application->getCountry());
		$this->addDeletableFieldCheckbox($companyInputs->getCompanyId(), $form->addCheckbox('companyIdSet'), $application->getCompanyId());
		$this->addDeletableFieldCheckbox($companyInputs->getCompanyTaxId(), $form->addCheckbox('companyTaxIdSet'), $application->getCompanyTaxId());
		$this->addDeletableFieldCheckbox($noteInput, $form->addCheckbox('noteSet'), $application->getNote());

		$containerName = 'statusHistoryDelete';
		$historyContainer = $form->addContainer($containerName);
		foreach ($this->trainingStatusHistory->getStatusHistory($application->getId()) as $history) {
			$historyContainer
				->addSubmit((string)$history->getId())
				->setValidationScope([$form[$containerName]])
				->onClick[] = function (SubmitButton $button) use ($application, $onStatusHistoryDeleteSuccess): void {
					$this->trainingStatusHistory->deleteHistoryRecord($application->getId(), (int)$button->getName());
					$onStatusHistoryDeleteSuccess();
				};
		}

		$form->onSuccess[] = function (UiForm $form) use ($application, $onSuccess): void {
			$values = $form->getFormValues();
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


	private function addPaymentInfo(UiForm $form): void
	{
		$form->addText('price', 'Cena bez DPH:')
			->setHtmlType('number')
			->setHtmlAttribute('step', 'any')
			->addRule($form::Float)
			->setHtmlAttribute('title', 'Po případné slevě');
		$form->addText('vatRate', 'DPH:')
			->setHtmlType('number');
		$form->addText('priceVat', 'Cena s DPH:')
			->setHtmlType('number')
			->setHtmlAttribute('step', 'any')
			->addRule($form::Float)
			->setHtmlAttribute('title', 'Po případné slevě');
		$form->addText('discount', 'Sleva:')
			->setHtmlType('number');
		$form->addText('invoiceId', 'Faktura č.:')
			->setHtmlType('number');
		$this->trainingControlsFactory->addPaidDate($form->addText('paid', 'Zaplaceno:'), false);
	}


	private function setApplication(UiForm $form, TrainingApplication $application): void
	{
		$vatRate = $application->getVatRate();
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
			'vatRate' => $vatRate ? $vatRate * 100 : $vatRate,
			'priceVat' => $application->getPriceVat(),
			'discount' => $application->getDiscount(),
			'invoiceId' => $application->getInvoiceId(),
			'paid' => $application->getPaid(),
			'date' => $application->getDateId(),
		];
		$form->setDefaults($values);
	}


	private function addDeletableFieldCheckbox(BaseControl $control, Checkbox $checkbox, ?string $fieldValue): void
	{
		$form = $control->getForm();
		$name = $control->getName();
		if ($form === null || $name === null) {
			return;
		}
		$control
			->setHtmlAttribute('class', $fieldValue === null ? 'transparent' : null)
			->setRequired(false);
		$checkbox
			->setDefaultValue($fieldValue !== null)
			->setHtmlAttribute('class', 'disableInput');
	}

}
