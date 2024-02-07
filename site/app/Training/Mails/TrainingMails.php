<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Training\Mails;

use DateTime;
use MichalSpacekCz\ShouldNotHappenException;
use MichalSpacekCz\Training\Applications\TrainingApplication;
use MichalSpacekCz\Training\Applications\TrainingApplications;
use MichalSpacekCz\Training\Dates\TrainingDates;
use MichalSpacekCz\Training\Dates\TrainingDateStatus;
use MichalSpacekCz\Training\Exceptions\TrainingDateDoesNotExistException;
use MichalSpacekCz\Training\Preliminary\PreliminaryTrainings;
use MichalSpacekCz\Training\Statuses\Statuses;
use MichalSpacekCz\Training\Venues\TrainingVenues;
use Nette\Bridges\ApplicationLatte\DefaultTemplate;
use Nette\Http\FileUpload;
use Nette\Mail\Mailer;
use Nette\Mail\Message;
use Nette\Utils\Html;
use ParagonIE\Halite\Alerts\HaliteAlert;
use RuntimeException;
use SodiumException;
use Tracy\Debugger;

readonly class TrainingMails
{

	private const REMINDER_DAYS = 5;


	public function __construct(
		private Mailer $mailer,
		private TrainingApplications $trainingApplications,
		private PreliminaryTrainings $trainingPreliminaryApplications,
		private TrainingDates $trainingDates,
		private Statuses $trainingStatuses,
		private TrainingVenues $trainingVenues,
		private TrainingMailMessageFactory $trainingMailMessageFactory,
		private string $emailFrom,
		private string $phoneNumber,
	) {
	}


	public function sendSignUpMail(
		int $applicationId,
		DefaultTemplate $template,
		string $recipientAddress,
		string $recipientName,
		DateTime $start,
		DateTime $end,
		string $training,
		Html $trainingName,
		bool $remote,
		?string $venueName,
		?string $venueNameExtended,
		?string $venueAddress,
		?string $venueCity,
	): void {
		Debugger::log("Sending sign-up email to application id: {$applicationId}, training: {$training}");

		$template->setFile(__DIR__ . '/templates/trainingSignUp.latte');

		$template->training = $training;
		$template->trainingName = $trainingName;
		$template->start = $start;
		$template->end = $end;
		$template->remote = $remote;
		$template->venueName = $venueName;
		$template->venueNameExtended = $venueNameExtended;
		$template->venueAddress = $venueAddress;
		$template->venueCity = $venueCity;

		$subject = 'Potvrzení registrace na školení ' . $trainingName;
		$this->sendMail($recipientAddress, $recipientName, $subject, $template);
	}


	/**
	 * @return list<TrainingApplication>
	 * @throws TrainingDateDoesNotExistException
	 * @throws HaliteAlert
	 * @throws SodiumException
	 */
	public function getApplications(): array
	{
		$applications = [];

		foreach ($this->trainingPreliminaryApplications->getPreliminaryWithDateSet() as $application) {
			$application->setNextStatus(Statuses::STATUS_INVITED);
			$applications[$application->getId()] = $application;
		}

		foreach ($this->trainingStatuses->getParentStatuses(Statuses::STATUS_INVITED) as $status) {
			foreach ($this->trainingApplications->getByStatus($status) as $application) {
				$dateId = $application->getDateId();
				if ($dateId !== null && $this->trainingDates->get($dateId)->getStatus() === TrainingDateStatus::Confirmed) {
					$application->setNextStatus(Statuses::STATUS_INVITED);
					$applications[$application->getId()] = $application;
				}
			}
		}

		foreach ($this->trainingStatuses->getParentStatuses(Statuses::STATUS_MATERIALS_SENT) as $status) {
			foreach ($this->trainingApplications->getByStatus($status) as $application) {
				if ($status !== Statuses::STATUS_ATTENDED || !$this->trainingStatuses->sendInvoiceAfter($application->getId())) {
					$application->setNextStatus(Statuses::STATUS_MATERIALS_SENT);
					$applications[$application->getId()] = $application;
				}
			}
		}

		foreach ($this->trainingStatuses->getParentStatuses(Statuses::STATUS_INVOICE_SENT) as $status) {
			foreach ($this->trainingApplications->getByStatus($status) as $application) {
				$application->setNextStatus(Statuses::STATUS_INVOICE_SENT);
				$applications[$application->getId()] = $application;
			}
		}

		foreach ($this->trainingStatuses->getParentStatuses(Statuses::STATUS_INVOICE_SENT_AFTER) as $status) {
			foreach ($this->trainingApplications->getByStatus($status) as $application) {
				if ($status !== Statuses::STATUS_ATTENDED || $this->trainingStatuses->sendInvoiceAfter($application->getId())) {
					$application->setNextStatus(Statuses::STATUS_INVOICE_SENT_AFTER);
					$applications[$application->getId()] = $application;
				}
			}
		}

		foreach ($this->trainingStatuses->getParentStatuses(Statuses::STATUS_REMINDED) as $status) {
			foreach ($this->trainingApplications->getByStatus($status) as $application) {
				if ($application->getStatus() === Statuses::STATUS_PRO_FORMA_INVOICE_SENT && $application->getPaid()) {
					continue;
				}
				$trainingStart = $application->getTrainingStart();
				if (!$trainingStart) {
					throw new ShouldNotHappenException(sprintf("Training application id '%s' with status '%s' should have a training start set", $application->getId(), $application->getStatus()));
				}
				if ($trainingStart->diff(new DateTime('now'))->days <= self::REMINDER_DAYS) {
					$application->setNextStatus(Statuses::STATUS_REMINDED);
					$applications[$application->getId()] = $application;
				}
			}
		}

		return array_values($applications);
	}


	public function sendInvitation(TrainingApplication $application, DefaultTemplate $template, string $additional): void
	{
		Debugger::log("Sending invitation email application id: {$application->getId()}, training: {$application->getTrainingAction()}");
		$message = $this->trainingMailMessageFactory->getMailMessage($application);
		$template->setFile($message->getFilename());
		$template->application = $application;
		$template->additional = $additional;
		$this->sendMail($application->getEmail(), $application->getName(), $message->getSubject(), $template);
	}


	public function sendMaterials(TrainingApplication $application, DefaultTemplate $template, bool $feedbackRequest, string $additional): void
	{
		Debugger::log("Sending materials email application id: {$application->getId()}, training: {$application->getTrainingAction()}");
		$message = $this->trainingMailMessageFactory->getMailMessage($application);
		$template->setFile($message->getFilename());
		$template->application = $application;
		$template->feedbackRequest = $feedbackRequest;
		$template->additional = $additional;
		$this->sendMail($application->getEmail(), $application->getName(), $message->getSubject(), $template);
	}


	public function sendInvoice(TrainingApplication $application, DefaultTemplate $template, FileUpload $invoice, ?string $cc, string $additional): void
	{
		Debugger::log("Sending invoice email to application id: {$application->getId()}, training: {$application->getTrainingAction()}");
		$message = $this->trainingMailMessageFactory->getMailMessage($application);
		$template->setFile($message->getFilename());
		$template->application = $application;
		$template->additional = $additional;
		$this->sendMail($application->getEmail(), $application->getName(), $message->getSubject(), $template, [$invoice->getUntrustedName() => $invoice->getTemporaryFile()], $cc);
	}


	public function sendReminder(TrainingApplication $application, DefaultTemplate $template, string $additional): void
	{
		Debugger::log("Sending reminder email application id: {$application->getId()}, training: {$application->getTrainingAction()}");
		$message = $this->trainingMailMessageFactory->getMailMessage($application);
		$template->setFile($message->getFilename());
		if (!$application->isRemote()) {
			$venueAction = $application->getVenueAction();
			if ($venueAction === null) {
				throw new ShouldNotHappenException("Application id '{$application->getId()}' for in-person training should have a venue set at this point");
			}
			$template->venue = $this->trainingVenues->get($venueAction);
		}
		$template->application = $application;
		$template->phoneNumber = $this->phoneNumber;
		$template->additional = $additional;
		$this->sendMail($application->getEmail(), $application->getName(), $message->getSubject(), $template);
	}


	/**
	 * @param array<string, string> $attachments name => filename
	 */
	private function sendMail(?string $recipientAddress, ?string $recipientName, string $subject, DefaultTemplate $template, array $attachments = [], ?string $cc = null): void
	{
		if ($recipientAddress === null || $recipientName === null) {
			throw new ShouldNotHappenException(sprintf("To send an email, training application must have both name and address set, this one has '%s <%s>'", $recipientName ?? '<null>', $recipientAddress ?? '<null>'));
		}
		$mail = new Message();
		foreach ($attachments as $name => $file) {
			$contents = file_get_contents($file);
			if ($contents === false) {
				throw new RuntimeException("Can't read file {$file}");
			}
			$mail->addAttachment($name, $contents);
		}
		$mail->setFrom($this->emailFrom)
			->addTo($recipientAddress, $recipientName)
			->addBcc($this->emailFrom)
			->setSubject($subject)
			->setBody((string)$template)
			->clearHeader('X-Mailer'); // Hide Nette Mailer banner
		if ($cc !== null) {
			$mail->addCc($cc);
		}
		$this->mailer->send($mail);
	}

}
