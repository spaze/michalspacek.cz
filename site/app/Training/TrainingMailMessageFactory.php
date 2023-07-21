<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Training;

use MichalSpacekCz\DateTime\DateTimeFormatter;
use MichalSpacekCz\ShouldNotHappenException;
use MichalSpacekCz\Training\Applications\TrainingApplication;

class TrainingMailMessageFactory
{

	public function __construct(
		private readonly Statuses $trainingStatuses,
		private readonly DateTimeFormatter $dateTimeFormatter,
	) {
	}


	public function getMailMessage(TrainingApplication $application): MailMessageAdmin
	{
		switch ($application->getNextStatus()) {
			case Statuses::STATUS_INVITED:
				return new MailMessageAdmin('invitation', 'Pozvánka na školení ' . $application->getTrainingName());
			case Statuses::STATUS_MATERIALS_SENT:
				return new MailMessageAdmin($application->isFamiliar() ? 'materialsFamiliar' : 'materials', 'Materiály ze školení ' . $application->getTrainingName());
			case Statuses::STATUS_INVOICE_SENT:
				return $application->getStatus() === Statuses::STATUS_PRO_FORMA_INVOICE_SENT || $this->trainingStatuses->historyContainsStatuses([Statuses::STATUS_PRO_FORMA_INVOICE_SENT], $application->getId())
					? new MailMessageAdmin('invoiceAfterProforma', 'Faktura za školení ' . $application->getTrainingName())
					: new MailMessageAdmin('invoice', 'Potvrzení registrace na školení ' . $application->getTrainingName() . ' a faktura');
			case Statuses::STATUS_INVOICE_SENT_AFTER:
				return new MailMessageAdmin($this->trainingStatuses->historyContainsStatuses([Statuses::STATUS_PRO_FORMA_INVOICE_SENT], $application->getId()) ? 'invoiceAfterProforma' : 'invoiceAfter', 'Faktura za školení ' . $application->getTrainingName());
			case Statuses::STATUS_REMINDED:
				if (!$application->getTrainingStart() || !$application->getTrainingEnd()) {
					throw new ShouldNotHappenException(sprintf("Training application id '%s' with next status '%s' should have both training start and end set", $application->getId(), $application->getNextStatus()));
				}
				$start = $this->dateTimeFormatter->localeIntervalDay($application->getTrainingStart(), $application->getTrainingEnd(), 'cs_CZ');
				return new MailMessageAdmin($application->isRemote() ? 'reminderRemote' : 'reminder', 'Připomenutí školení ' . $application->getTrainingName() . ' ' . $start);
			default:
				throw new ShouldNotHappenException(sprintf("Unsupported next status: '%s'", $application->getNextStatus() ?? '<null>'));
		}
	}

}
