<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Form;

use MichalSpacekCz\ShouldNotHappenException;
use MichalSpacekCz\Templating\TemplateFactory;
use MichalSpacekCz\Training\Applications\TrainingApplication;
use MichalSpacekCz\Training\Applications\TrainingApplicationStorage;
use MichalSpacekCz\Training\ApplicationStatuses\TrainingApplicationStatus;
use MichalSpacekCz\Training\ApplicationStatuses\TrainingApplicationStatuses;
use MichalSpacekCz\Training\Mails\TrainingMails;
use Nette\Application\Application as NetteApplication;
use Nette\Application\UI\Presenter;
use Nette\Forms\Form;
use Nette\Http\FileUpload;
use Nette\Utils\ArrayHash;

final readonly class TrainingMailsOutboxFormFactory
{

	public function __construct(
		private FormFactory $factory,
		private TrainingApplicationStorage $trainingApplicationStorage,
		private TrainingApplicationStatuses $trainingApplicationStatuses,
		private TrainingMails $trainingMails,
		private TemplateFactory $templateFactory,
		private NetteApplication $netteApplication,
	) {
	}


	/**
	 * @param callable(int): void $onSuccess
	 * @param array<int, TrainingApplication> $applications
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
				case TrainingApplicationStatus::Invited:
					$checked = (bool)$application->getDateId();
					$disabled = !$checked;
					if ($application->getDateId() === null) {
						$sendCheckboxTitle['dateId'] = 'Není vybrán datum';
					}
					break;
				case TrainingApplicationStatus::MaterialsSent:
					$uploadedAfterStart = $application->getFiles()->getNewestFile()?->getAdded() > $application->getTrainingStart();
					$checked = $filesCount > 0 && $uploadedAfterStart;
					$disabled = !$checked;
					if ($filesCount === 0) {
						$sendCheckboxTitle['files'] = 'Není nahrán žádný soubor';
					} elseif (!$uploadedAfterStart) {
						$sendCheckboxTitle['files'] = 'Není nahrán žádný nový soubor (s časem nahrání po začátku školení)';
					}
					break;
				case TrainingApplicationStatus::InvoiceSent:
				case TrainingApplicationStatus::InvoiceSentAfter:
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
				case TrainingApplicationStatus::Reminded:
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
			if ($sendCheckboxTitle !== []) {
				$send->setHtmlAttribute('title', implode("\n", $sendCheckboxTitle));
			}
			$additionalInputs[] = $applicationIdsContainer->addTextArea('additional')
				->setHtmlAttribute('placeholder', 'Dodatečný text')
				->setHtmlAttribute('cols', 80)
				->setHtmlAttribute('rows', 3);
			switch ($application->getNextStatus()) {
				case TrainingApplicationStatus::MaterialsSent:
					$feedbackRequestCheckbox = $applicationIdsContainer->addCheckbox('feedbackRequest', 'Požádat o zhodnocení')
						->setDefaultValue($application->getFeedbackHref());
					if ($application->getFeedbackHref() === null) {
						$feedbackRequestCheckbox->setHtmlAttribute('title', 'Odkaz na feedback formulář není nastaven')
							->setDisabled(true);
					}
					break;
				case TrainingApplicationStatus::InvoiceSent:
				case TrainingApplicationStatus::InvoiceSentAfter:
					$applicationIdsContainer->addText('invoiceId')
						->setHtmlType('number')
						->setHtmlAttribute('placeholder', 'Faktura č.')
						->setHtmlAttribute('title', 'Faktura č.')
						->setDefaultValue($application->getInvoiceId())
						->addConditionOn($send, Form::Filled)
						->addRule(Form::Filled, 'Chybí číslo faktury');
					$applicationIdsContainer->addUpload('invoice')
						->setHtmlAttribute('title', 'Faktura v PDF')
						->setHtmlAttribute('accept', 'application/pdf')
						->addConditionOn($send, Form::Filled)
						->addRule(Form::Filled, 'Chybí faktura')
						->addRule(Form::MimeType, 'Faktura není v PDF', 'application/pdf');
					$applicationIdsContainer->addEmail('cc', 'Cc:')->setRequired(false);
					break;
			}
		}
		$form->addSubmit('submit', 'Odeslat');
		$form->onSuccess[] = function (UiForm $form) use ($applications, $onSuccess): void {
			$values = $form->getFormValues();
			assert($values->applications instanceof ArrayHash);
			$sent = 0;
			foreach ($values->applications as $id => $data) {
				assert($data instanceof ArrayHash);
				assert(is_string($data->additional));
				if (!$data->send || !isset($applications[$id])) {
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

				if ($nextStatus === TrainingApplicationStatus::Invited) {
					$this->trainingMails->sendInvitation($applications[$id], $template, $additional);
					$this->trainingApplicationStatuses->updateStatus($id, TrainingApplicationStatus::Invited);
					$sent++;
				}

				if ($nextStatus === TrainingApplicationStatus::MaterialsSent) {
					assert(is_bool($data->feedbackRequest));
					$this->trainingMails->sendMaterials($applications[$id], $template, $data->feedbackRequest, $additional);
					$this->trainingApplicationStatuses->updateStatus($id, TrainingApplicationStatus::MaterialsSent);
					$sent++;
				}

				if (in_array($nextStatus, [TrainingApplicationStatus::InvoiceSent, TrainingApplicationStatus::InvoiceSentAfter], true)) {
					assert($data->invoice instanceof FileUpload);
					assert(is_string($data->invoiceId));
					assert(is_string($data->cc));
					if ($data->invoice->isOk()) {
						$this->trainingApplicationStorage->updateApplicationInvoiceData($id, $data->invoiceId);
						$applications[$id]->setInvoiceId((int)$data->invoiceId);
						$this->trainingMails->sendInvoice($applications[$id], $template, $data->invoice, $data->cc ?: null, $additional);
						$this->trainingApplicationStatuses->updateStatus($id, $nextStatus);
						$sent++;
					}
				}

				if ($nextStatus === TrainingApplicationStatus::Reminded) {
					$this->trainingMails->sendReminder($applications[$id], $template, $additional);
					$this->trainingApplicationStatuses->updateStatus($id, TrainingApplicationStatus::Reminded);
					$sent++;
				}
			}
			$onSuccess($sent);
		};
		$form->onAnchor[] = function () use ($additionalInputs): void {
			foreach ($additionalInputs as $additionalInput) {
				if ($additionalInput->getValue() !== '') {
					$additionalInput->setHtmlAttribute('class', 'expanded');
				}
			}
		};
		return $form;
	}

}
