<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Training\Mails;

use MichalSpacekCz\DateTime\DateTimeFormatter;
use MichalSpacekCz\ShouldNotHappenException;
use MichalSpacekCz\Training\Applications\TrainingApplication;
use MichalSpacekCz\Training\ApplicationStatuses\TrainingApplicationStatuses;
use MichalSpacekCz\Training\ApplicationStatuses\TrainingApplicationStatusHistory;

readonly class TrainingMailMessageFactory
{

	public function __construct(
		private TrainingApplicationStatusHistory $trainingApplicationStatusHistory,
		private DateTimeFormatter $dateTimeFormatter,
	) {
	}


	public function getMailMessage(TrainingApplication $application): MailMessageAdmin
	{
		$nextStatus = $application->getNextStatus();
		switch ($nextStatus) {
			case TrainingApplicationStatuses::STATUS_INVITED:
				return new MailMessageAdmin('invitation', 'Pozvánka na školení ' . $application->getTrainingName());
			case TrainingApplicationStatuses::STATUS_MATERIALS_SENT:
				return new MailMessageAdmin($application->isFamiliar() ? 'materialsFamiliar' : 'materials', 'Materiály ze školení ' . $application->getTrainingName());
			case TrainingApplicationStatuses::STATUS_INVOICE_SENT:
				return $application->getStatus() === TrainingApplicationStatuses::STATUS_PRO_FORMA_INVOICE_SENT || $this->trainingApplicationStatusHistory->historyContainsStatuses([TrainingApplicationStatuses::STATUS_PRO_FORMA_INVOICE_SENT], $application->getId())
					? new MailMessageAdmin('invoiceAfterProforma', 'Faktura za školení ' . $application->getTrainingName())
					: new MailMessageAdmin('invoice', 'Potvrzení registrace na školení ' . $application->getTrainingName() . ' a faktura');
			case TrainingApplicationStatuses::STATUS_INVOICE_SENT_AFTER:
				return new MailMessageAdmin($this->trainingApplicationStatusHistory->historyContainsStatuses([TrainingApplicationStatuses::STATUS_PRO_FORMA_INVOICE_SENT], $application->getId()) ? 'invoiceAfterProforma' : 'invoiceAfter', 'Faktura za školení ' . $application->getTrainingName());
			case TrainingApplicationStatuses::STATUS_REMINDED:
				$trainingStart = $application->getTrainingStart();
				$trainingEnd = $application->getTrainingEnd();
				if (!$trainingStart || !$trainingEnd) {
					throw new ShouldNotHappenException(sprintf("Training application id '%s' with next status '%s' should have both training start and end set", $application->getId(), $nextStatus));
				}
				$start = $this->dateTimeFormatter->localeIntervalDay($trainingStart, $trainingEnd, 'cs_CZ');
				return new MailMessageAdmin($application->isRemote() ? 'reminderRemote' : 'reminder', 'Připomenutí školení ' . $application->getTrainingName() . ' ' . $start);
			default:
				throw new ShouldNotHappenException(sprintf("Unsupported next status: '%s'", $nextStatus ?? '<null>'));
		}
	}

}
