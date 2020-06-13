<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Form;

use MichalSpacekCz\Training\Statuses;
use Nette\ComponentModel\IContainer;
use Nette\Database\Row;

class TrainingMailsOutbox extends ProtectedForm
{

	/**
	 * @param IContainer $parent
	 * @param string $name
	 * @param Row[] $applications
	 */
	public function __construct(IContainer $parent, string $name, array $applications)
	{
		parent::__construct($parent, $name);

		$applicationsContainer = $this->addContainer('applications');

		foreach ($applications as $application) {
			$applicationIdsContainer = $applicationsContainer->addContainer($application->id);
			$checked = true;
			$disabled = false;
			switch ($application->nextStatus) {
				case Statuses::STATUS_MATERIALS_SENT:
					$checked = (bool)$application->files;
					$disabled = !$checked;
					break;
				case Statuses::STATUS_INVOICE_SENT:
				case Statuses::STATUS_INVOICE_SENT_AFTER:
					$checked = ($application->price && $application->vatRate && $application->priceVat);
					$disabled = !$checked;
					break;
			}
			$send = $applicationIdsContainer->addCheckbox('send')
				->setDefaultValue($checked)
				->setDisabled($disabled)
				->setHtmlAttribute('class', 'send');
			$applicationIdsContainer->addTextArea('additional', null)
				->setHtmlAttribute('placeholder', 'Dodatečný text')
				->setHtmlAttribute('cols', 80)
				->setHtmlAttribute('rows', 3);
			switch ($application->nextStatus) {
				case Statuses::STATUS_MATERIALS_SENT:
					$applicationIdsContainer->addCheckbox('feedbackRequest', 'Požádat o zhodnocení')
						->setDefaultValue(true);
					break;
				case Statuses::STATUS_INVOICE_SENT:
				case Statuses::STATUS_INVOICE_SENT_AFTER:
					$applicationIdsContainer->addText('invoiceId')
						->setHtmlType('number')
						->setHtmlAttribute('placeholder', 'Faktura č.')
						->setHtmlAttribute('title', 'Faktura č.')
						->setDefaultValue($application->invoiceId)
						->addConditionOn($send, self::FILLED)
							->addRule(self::FILLED, 'Chybí číslo faktury');
					$applicationIdsContainer->addUpload('invoice')
						->setHtmlAttribute('title', 'Faktura v PDF')
						->setHtmlAttribute('accept', 'application/pdf')
						->addConditionOn($send, self::FILLED)
							->addRule(self::FILLED, 'Chybí faktura')
							->addRule(self::MIME_TYPE, 'Faktura není v PDF', 'application/pdf');
					break;
			}
		}

		$this->addSubmit('submit', 'Odeslat');
	}

}
