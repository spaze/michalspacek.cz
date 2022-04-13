<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Form;

use MichalSpacekCz\Templating\TemplateFactory;
use MichalSpacekCz\Training\Applications;
use MichalSpacekCz\Training\Files\TrainingFilesCollection;
use MichalSpacekCz\Training\Mails;
use MichalSpacekCz\Training\Statuses;
use Nette\Application\Application as NetteApplication;
use Nette\Application\UI\Form;
use Nette\Application\UI\Presenter;
use Nette\Database\Row;
use stdClass;

class TrainingMailsOutboxFactory
{

	private FormFactory $factory;

	private Applications $trainingApplications;

	private Statuses $trainingStatuses;

	private Mails $trainingMails;

	private TemplateFactory $templateFactory;

	private NetteApplication $netteApplication;


	public function __construct(
		FormFactory $factory,
		Applications $trainingApplications,
		Statuses $trainingStatuses,
		Mails $trainingMails,
		TemplateFactory $templateFactory,
		NetteApplication $netteApplication,
	) {
		$this->factory = $factory;
		$this->trainingApplications = $trainingApplications;
		$this->trainingStatuses = $trainingStatuses;
		$this->trainingMails = $trainingMails;
		$this->templateFactory = $templateFactory;
		$this->netteApplication = $netteApplication;
	}


	/**
	 * @param callable $onSuccess
	 * @param Row[] $applications
	 * @return Form
	 */
	public function create(callable $onSuccess, array $applications): Form
	{
		$form = $this->factory->create();

		$applicationsContainer = $form->addContainer('applications');

		foreach ($applications as $application) {
			$applicationIdsContainer = $applicationsContainer->addContainer($application->id);
			$checked = true;
			$disabled = false;
			$sendCheckboxTitle = [];
			/** @var TrainingFilesCollection $files */
			$files = $application->files;
			$filesCount = count($files);
			switch ($application->nextStatus) {
				case Statuses::STATUS_INVITED:
					$checked = isset($application->dateId);
					$disabled = !$checked;
					if (!isset($application->dateId)) {
						$sendCheckboxTitle['dateId'] = 'Není vybrán datum';
					}
					break;
				case Statuses::STATUS_MATERIALS_SENT:
					$uploadedAfterStart = $files->getNewestFile()?->getAdded() > $application->trainingStart;
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
					$checked = ($application->price && $application->vatRate && $application->priceVat);
					$disabled = !$checked;
					if (!$application->price) {
						$sendCheckboxTitle['price'] = 'Chybí cena';
					}
					if (!$application->vatRate) {
						$sendCheckboxTitle['vatRate'] = 'Chybí DPH';
					}
					if (!$application->priceVat) {
						$sendCheckboxTitle['priceVat'] = 'Chybí cena s DPH';
					}
					break;
				case Statuses::STATUS_REMINDED:
					if ($application->remote) {
						$checked = $filesCount > 0 && (bool)$application->remoteUrl;
						$disabled = !$checked;
						if ($filesCount === 0) {
							$sendCheckboxTitle['files'] = 'Není nahrán žádný soubor';
						}
						if (!$application->remoteUrl) {
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
			$applicationIdsContainer->addTextArea('additional')
				->setHtmlAttribute('placeholder', 'Dodatečný text')
				->setHtmlAttribute('cols', 80)
				->setHtmlAttribute('rows', 3);
			switch ($application->nextStatus) {
				case Statuses::STATUS_MATERIALS_SENT:
					$feedbackRequestCheckbox = $applicationIdsContainer->addCheckbox('feedbackRequest', 'Požádat o zhodnocení')
						->setDefaultValue($application->feedbackHref);
					if (!$application->feedbackHref) {
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
						->setDefaultValue($application->invoiceId)
						->addConditionOn($send, $form::FILLED)
						->addRule($form::FILLED, 'Chybí číslo faktury');
					$applicationIdsContainer->addUpload('invoice')
						->setHtmlAttribute('title', 'Faktura v PDF')
						->setHtmlAttribute('accept', 'application/pdf')
						->addConditionOn($send, $form::FILLED)
						->addRule($form::FILLED, 'Chybí faktura')
						->addRule($form::MIME_TYPE, 'Faktura není v PDF', 'application/pdf');
					$applicationIdsContainer->addEmail('cc', 'Cc:')->setRequired(false);
					break;
			}
		}
		$form->addSubmit('submit', 'Odeslat');
		$form->onSuccess[] = function (Form $form, stdClass $values) use ($applications, $onSuccess): void {
			$sent = 0;
			/** @var stdClass $data */
			foreach ($values->applications as $id => $data) {
				if (empty($data->send) || !isset($applications[$id])) {
					continue;
				}
				$additional = trim($data->additional);
				/** @var Presenter $presenter */
				$presenter = $this->netteApplication->getPresenter();
				$template = $this->templateFactory->createTemplate($presenter);

				if ($applications[$id]->nextStatus === Statuses::STATUS_INVITED) {
					$this->trainingMails->sendInvitation($applications[$id], $template, $additional);
					$this->trainingStatuses->updateStatus($id, Statuses::STATUS_INVITED);
					$sent++;
				}

				if ($applications[$id]->nextStatus === Statuses::STATUS_MATERIALS_SENT) {
					$this->trainingMails->sendMaterials($applications[$id], $template, $data->feedbackRequest ?? false, $additional);
					$this->trainingStatuses->updateStatus($id, Statuses::STATUS_MATERIALS_SENT);
					$sent++;
				}

				if (in_array($applications[$id]->nextStatus, [Statuses::STATUS_INVOICE_SENT, Statuses::STATUS_INVOICE_SENT_AFTER])) {
					if ($data->invoice->isOk()) {
						$this->trainingApplications->updateApplicationInvoiceData($id, $data->invoiceId);
						$applications[$id]->invoiceId = $data->invoiceId;
						$this->trainingMails->sendInvoice($applications[$id], $template, $data->invoice, $data->cc ?: null, $additional);
						$this->trainingStatuses->updateStatus($id, $applications[$id]->nextStatus);
						$sent++;
					}
				}

				if ($applications[$id]->nextStatus === Statuses::STATUS_REMINDED) {
					$this->trainingMails->sendReminder($applications[$id], $template, $additional);
					$this->trainingStatuses->updateStatus($id, Statuses::STATUS_REMINDED);
					$sent++;
				}
			}
			$onSuccess($sent);
		};
		return $form;
	}

}
