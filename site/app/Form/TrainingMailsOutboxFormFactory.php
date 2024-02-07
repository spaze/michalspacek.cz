<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Form;

use MichalSpacekCz\ShouldNotHappenException;
use MichalSpacekCz\Templating\TemplateFactory;
use MichalSpacekCz\Training\Applications\TrainingApplication;
use MichalSpacekCz\Training\Applications\TrainingApplicationStorage;
use MichalSpacekCz\Training\Mails\TrainingMails;
use MichalSpacekCz\Training\Statuses\Statuses;
use Nette\Application\Application as NetteApplication;
use Nette\Application\UI\Presenter;
use stdClass;

readonly class TrainingMailsOutboxFormFactory
{

	public function __construct(
		private FormFactory $factory,
		private TrainingApplicationStorage $trainingApplicationStorage,
		private Statuses $trainingStatuses,
		private TrainingMails $trainingMails,
		private TemplateFactory $templateFactory,
		private NetteApplication $netteApplication,
	) {
	}


	/**
	 * @param callable(int): void $onSuccess
	 * @param list<TrainingApplication> $applications
	 */
	public function create(callable $onSuccess, array $applications): UiForm
	{
		$form = $this->factory->create();

		$applicationsContainer = $form->addContainer('applications');
		$additionalInputs = [];

		foreach ($applications as $application) {
			$applicationIdsContainer = $applicationsContainer->addContainer($application->getId());
			$checked = true;
			$disabled = false;
			$sendCheckboxTitle = [];
			$filesCount = count($application->getFiles());
			switch ($application->getNextStatus()) {
				case Statuses::STATUS_INVITED:
					$checked = (bool)$application->getDateId();
					$disabled = !$checked;
					if ($application->getDateId() === null) {
						$sendCheckboxTitle['dateId'] = 'Není vybrán datum';
					}
					break;
				case Statuses::STATUS_MATERIALS_SENT:
					$uploadedAfterStart = $application->getFiles()->getNewestFile()?->getAdded() > $application->getTrainingStart();
					$checked = $filesCount > 0 && $uploadedAfterStart;
					$disabled = !$checked;
					if ($filesCount === 0) {
						$sendCheckboxTitle['files'] = 'Není nahrán žádný soubor';
					} elseif (!$uploadedAfterStart) {
						$sendCheckboxTitle['files'] = 'Není nahrán žádný nový soubor (s časem nahrání po začátku školení)';
					}
					break;
				case Statuses::STATUS_INVOICE_SENT:
				case Statuses::STATUS_INVOICE_SENT_AFTER:
					$price = $application->getPrice();
					if ($price === 0.0) {
						$price = null;
					}
					$vatRate = $application->getVatRate();
					if ($vatRate === 0.0) {
						$vatRate = null;
					}
					$priceVat = $application->getPriceVat();
					if ($priceVat === 0.0) {
						$priceVat = null;
					}
					$checked = $price !== null && $vatRate !== null && $priceVat !== null;
					$disabled = !$checked;
					if ($price === null) {
						$sendCheckboxTitle['price'] = 'Chybí cena';
					}
					if ($vatRate === null) {
						$sendCheckboxTitle['vatRate'] = 'Chybí DPH';
					}
					if ($priceVat === null) {
						$sendCheckboxTitle['priceVat'] = 'Chybí cena s DPH';
					}
					break;
				case Statuses::STATUS_REMINDED:
					if ($application->isRemote()) {
						$checked = $filesCount > 0 && $application->getRemoteUrl() !== null;
						$disabled = !$checked;
						if ($filesCount === 0) {
							$sendCheckboxTitle['files'] = 'Není nahrán žádný soubor';
						}
						if ($application->getRemoteUrl() === null) {
							$sendCheckboxTitle['remoteUrl'] = 'Chybí online URL';
						}
					}
					break;
			}
			$send = $applicationIdsContainer->addCheckbox('send')
				->setDefaultValue($checked)
				->setDisabled($disabled)
				->setHtmlAttribute('class', 'send');
			if ($sendCheckboxTitle) {
				$send->setHtmlAttribute('title', implode("\n", $sendCheckboxTitle));
			}
			$additionalInputs[] = $applicationIdsContainer->addTextArea('additional')
				->setHtmlAttribute('placeholder', 'Dodatečný text')
				->setHtmlAttribute('cols', 80)
				->setHtmlAttribute('rows', 3);
			switch ($application->getNextStatus()) {
				case Statuses::STATUS_MATERIALS_SENT:
					$feedbackRequestCheckbox = $applicationIdsContainer->addCheckbox('feedbackRequest', 'Požádat o zhodnocení')
						->setDefaultValue($application->getFeedbackHref());
					if ($application->getFeedbackHref() === null) {
						$feedbackRequestCheckbox->setHtmlAttribute('title', 'Odkaz na feedback formulář není nastaven')
							->setDisabled(true);
					}
					break;
				case Statuses::STATUS_INVOICE_SENT:
				case Statuses::STATUS_INVOICE_SENT_AFTER:
					$applicationIdsContainer->addText('invoiceId')
						->setHtmlType('number')
						->setHtmlAttribute('placeholder', 'Faktura č.')
						->setHtmlAttribute('title', 'Faktura č.')
						->setDefaultValue($application->getInvoiceId())
						->addConditionOn($send, $form::Filled)
						->addRule($form::Filled, 'Chybí číslo faktury');
					$applicationIdsContainer->addUpload('invoice')
						->setHtmlAttribute('title', 'Faktura v PDF')
						->setHtmlAttribute('accept', 'application/pdf')
						->addConditionOn($send, $form::Filled)
						->addRule($form::Filled, 'Chybí faktura')
						->addRule($form::MimeType, 'Faktura není v PDF', 'application/pdf');
					$applicationIdsContainer->addEmail('cc', 'Cc:')->setRequired(false);
					break;
			}
		}
		$form->addSubmit('submit', 'Odeslat');
		$form->onSuccess[] = function (UiForm $form) use ($applications, $onSuccess): void {
			$values = $form->getFormValues();
			$sent = 0;
			foreach ($values->applications as $id => $data) {
				if (!$data instanceof stdClass) {
					throw new ShouldNotHappenException(sprintf("The presenter should be a '%s' but it's a %s", stdClass::class, get_debug_type($data)));
				}
				if (empty($data->send) || !isset($applications[$id])) {
					continue;
				}
				$nextStatus = $applications[$id]->getNextStatus();
				if ($nextStatus === null) {
					throw new ShouldNotHappenException("Training application id '{$id}' should have a next status set");
				}
				$additional = trim($data->additional);
				$presenter = $this->netteApplication->getPresenter();
				if (!$presenter instanceof Presenter) {
					throw new ShouldNotHappenException(sprintf("The presenter should be a '%s' but it's a %s", Presenter::class, get_debug_type($presenter)));
				}
				$template = $this->templateFactory->createTemplate($presenter);

				if ($nextStatus === Statuses::STATUS_INVITED) {
					$this->trainingMails->sendInvitation($applications[$id], $template, $additional);
					$this->trainingStatuses->updateStatus($id, Statuses::STATUS_INVITED);
					$sent++;
				}

				if ($nextStatus === Statuses::STATUS_MATERIALS_SENT) {
					$this->trainingMails->sendMaterials($applications[$id], $template, $data->feedbackRequest ?? false, $additional);
					$this->trainingStatuses->updateStatus($id, Statuses::STATUS_MATERIALS_SENT);
					$sent++;
				}

				if (in_array($nextStatus, [Statuses::STATUS_INVOICE_SENT, Statuses::STATUS_INVOICE_SENT_AFTER])) {
					if ($data->invoice->isOk()) {
						$this->trainingApplicationStorage->updateApplicationInvoiceData($id, $data->invoiceId);
						$applications[$id]->setInvoiceId((int)$data->invoiceId);
						$this->trainingMails->sendInvoice($applications[$id], $template, $data->invoice, $data->cc ?: null, $additional);
						$this->trainingStatuses->updateStatus($id, $nextStatus);
						$sent++;
					}
				}

				if ($nextStatus === Statuses::STATUS_REMINDED) {
					$this->trainingMails->sendReminder($applications[$id], $template, $additional);
					$this->trainingStatuses->updateStatus($id, Statuses::STATUS_REMINDED);
					$sent++;
				}
			}
			$onSuccess($sent);
		};
		$form->onAnchor[] = function () use ($additionalInputs): void {
			foreach ($additionalInputs as $additionalInput) {
				if ($additionalInput->getValue()) {
					$additionalInput->setHtmlAttribute('class', 'expanded');
				}
			}
		};
		return $form;
	}

}
