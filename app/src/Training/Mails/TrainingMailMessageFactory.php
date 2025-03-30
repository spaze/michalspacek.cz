<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Training\Mails;

use MichalSpacekCz\DateTime\DateTimeFormatter;
use MichalSpacekCz\ShouldNotHappenException;
use MichalSpacekCz\Training\Applications\TrainingApplication;
use MichalSpacekCz\Training\ApplicationStatuses\TrainingApplicationStatus;
use MichalSpacekCz\Training\ApplicationStatuses\TrainingApplicationStatusHistory;

final readonly class TrainingMailMessageFactory
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
			case TrainingApplicationStatus::Invited:
				return new MailMessageAdmin('invitation', 'Pozvánka na školení ' . $application->getTrainingName()->render());
			case TrainingApplicationStatus::MaterialsSent:
				return new MailMessageAdmin($application->isFamiliar() ? 'materialsFamiliar' : 'materials', 'Materiály ze školení ' . $application->getTrainingName()->render());
			case TrainingApplicationStatus::InvoiceSent:
				return $application->getStatus() === TrainingApplicationStatus::ProFormaInvoiceSent || $this->trainingApplicationStatusHistory->historyContainsStatuses([TrainingApplicationStatus::ProFormaInvoiceSent], $application->getId())
					? new MailMessageAdmin('invoiceAfterProforma', 'Faktura za školení ' . $application->getTrainingName()->render())
					: new MailMessageAdmin('invoice', 'Potvrzení registrace na školení ' . $application->getTrainingName()->render() . ' a faktura');
			case TrainingApplicationStatus::InvoiceSentAfter:
				return new MailMessageAdmin($this->trainingApplicationStatusHistory->historyContainsStatuses([TrainingApplicationStatus::ProFormaInvoiceSent], $application->getId()) ? 'invoiceAfterProforma' : 'invoiceAfter', 'Faktura za školení ' . $application->getTrainingName()->render());
			case TrainingApplicationStatus::Reminded:
				$trainingStart = $application->getTrainingStart();
				$trainingEnd = $application->getTrainingEnd();
				if ($trainingStart === null || $trainingEnd === null) {
					throw new ShouldNotHappenException(sprintf("Training application id '%s' with next status '%s' should have both training start and end set", $application->getId(), TrainingApplicationStatus::Reminded->value));
				}
				$start = $this->dateTimeFormatter->localeIntervalDay($trainingStart, $trainingEnd, 'cs_CZ');
				return new MailMessageAdmin($application->isRemote() ? 'reminderRemote' : 'reminder', 'Připomenutí školení ' . $application->getTrainingName()->render() . ' ' . $start);
			default:
				throw new ShouldNotHappenException(sprintf("Unsupported next status: '%s'", $nextStatus !== null ? $nextStatus->value : '<null>'));
		}
	}

}
