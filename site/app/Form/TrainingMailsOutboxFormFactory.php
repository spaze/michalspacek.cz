<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Form;

use MichalSpacekCz\ShouldNotHappenException;
use MichalSpacekCz\Templating\TemplateFactory;
use MichalSpacekCz\Training\Applications\TrainingApplicationStorage;
use MichalSpacekCz\Training\Files\TrainingFilesCollection;
use MichalSpacekCz\Training\Mails;
use MichalSpacekCz\Training\Statuses;
use Nette\Application\Application as NetteApplication;
use Nette\Application\UI\Form;
use Nette\Application\UI\Presenter;
use Nette\Database\Row;
use stdClass;

class TrainingMailsOutboxFormFactory
{

	public function __construct(
		private readonly FormFactory $factory,
		private readonly TrainingApplicationStorage $trainingApplicationStorage,
		private readonly Statuses $trainingStatuses,
		private readonly Mails $trainingMails,
		private readonly TemplateFactory $templateFactory,
		private readonly NetteApplication $netteApplication,
	) {
	}


	/**
	 * @param callable(int): void $onSuccess
	 * @param Row[] $applications
	 * @return Form
	 */
	public function create(callable $onSuccess, array $applications): Form
	{
		$form = $this->factory->create();

		$applicationsContainer = $form->addContainer('applications');
		$additionalInputs = [];

		foreach ($applications as $application) {
			$applicationIdsContainer = $applicationsContainer->addContainer($application->id);
			$checked = true;
			$disabled = false;
			$sendCheckboxTitle = [];
			if (!$application->files instanceof TrainingFilesCollection) {
				throw new ShouldNotHappenException(sprintf("The files property should be a '%s' but it's a %s", TrainingFilesCollection::class, get_debug_type($application->files)));
			}
			$filesCount = count($application->files);
			switch ($application->nextStatus) {
				case Statuses::STATUS_INVITED:
					$checked = isset($application->dateId);
					$disabled = !$checked;
					if (!isset($application->dateId)) {
						$sendCheckboxTitle['dateId'] = 'Není vybrán datum';
					}
					break;
				case Statuses::STATUS_MATERIALS_SENT:
					$uploadedAfterStart = $application->files->getNewestFile()?->getAdded() > $application->trainingStart;
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
						$checked = $filesCount > 0 && $application->remoteUrl;
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
			$additionalInputs[] = $applicationIdsContainer->addTextArea('additional')
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
		$form->onSuccess[] = function (Form $form) use ($applications, $onSuccess): void {
			$values = $form->getValues();
			$sent = 0;
			foreach ($values->applications as $id => $data) {
				if (!$data instanceof stdClass) {
					throw new ShouldNotHappenException(sprintf("The presenter should be a '%s' but it's a %s", stdClass::class, get_debug_type($data)));
				}
				if (empty($data->send) || !isset($applications[$id])) {
					continue;
				}
				$additional = trim($data->additional);
				$presenter = $this->netteApplication->getPresenter();
				if (!$presenter instanceof Presenter) {
					throw new ShouldNotHappenException(sprintf("The presenter should be a '%s' but it's a %s", Presenter::class, get_debug_type($presenter)));
				}
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
						$this->trainingApplicationStorage->updateApplicationInvoiceData($id, $data->invoiceId);
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
