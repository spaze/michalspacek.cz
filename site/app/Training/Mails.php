<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Training;

use DateTime;
use MichalSpacekCz\ShouldNotHappenException;
use MichalSpacekCz\Training\Applications\TrainingApplication;
use MichalSpacekCz\Training\Applications\TrainingApplications;
use MichalSpacekCz\Training\Dates\TrainingDates;
use MichalSpacekCz\Training\Dates\TrainingDateStatus;
use MichalSpacekCz\Training\Exceptions\TrainingDateDoesNotExistException;
use MichalSpacekCz\Training\Preliminary\PreliminaryTrainings;
use MichalSpacekCz\Training\Venues\TrainingVenues;
use Nette\Bridges\ApplicationLatte\DefaultTemplate;
use Nette\Http\FileUpload;
use Nette\Mail\Mailer;
use Nette\Mail\Message;
use Nette\Utils\Html;
use ParagonIE\Halite\Alerts\HaliteAlert;
use SodiumException;
use Tracy\Debugger;

class Mails
{

	private const REMINDER_DAYS = 5;


	public function __construct(
		private readonly Mailer $mailer,
		private readonly TrainingApplications $trainingApplications,
		private readonly PreliminaryTrainings $trainingPreliminaryApplications,
		private readonly TrainingDates $trainingDates,
		private readonly Statuses $trainingStatuses,
		private readonly TrainingVenues $trainingVenues,
		private readonly TrainingMailMessageFactory $trainingMailMessageFactory,
		private readonly string $emailFrom,
		private readonly string $phoneNumber,
	) {
	}


	/**
	 * @param int $applicationId
	 * @param DefaultTemplate $template
	 * @param string $recipientAddress
	 * @param string $recipientName
	 * @param DateTime $start
	 * @param DateTime $end
	 * @param string $training
	 * @param Html<Html|string> $trainingName
	 * @param bool $remote
	 * @param string|null $venueName
	 * @param string|null $venueNameExtended
	 * @param string|null $venueAddress
	 * @param string|null $venueCity
	 */
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

		$template->setFile(__DIR__ . '/mails/trainingSignUp.latte');

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
				if (
					$application->getDateId()
					&& $this->trainingDates->get($application->getDateId())->getStatus() === TrainingDateStatus::Confirmed
				) {
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
				if (!$application->getTrainingStart()) {
					throw new ShouldNotHappenException(sprintf("Training application id '%s' with status '%s' should have a training start set", $application->getId(), $application->getStatus()));
				}
				if ($application->getTrainingStart()->diff(new DateTime('now'))->days <= self::REMINDER_DAYS) {
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
			if (!$application->getVenueAction()) {
				throw new ShouldNotHappenException("Application id '{$application->getId()}' for in-person training should have a venue set at this point");
			}
			$template->venue = $this->trainingVenues->get($application->getVenueAction());
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
		if (!$recipientAddress || !$recipientName) {
			throw new ShouldNotHappenException(sprintf("To send an email, training application must have both name and address set, this one has '%s <%s>'", $recipientName ?? '<null>', $recipientAddress ?? '<null>'));
		}
		$mail = new Message();
		foreach ($attachments as $name => $file) {
			$contents = file_get_contents($file);
			if (!$contents) {
				throw new \RuntimeException("Can't read file {$file}");
			}
			$mail->addAttachment($name, $contents);
		}
		$mail->setFrom($this->emailFrom)
			->addTo($recipientAddress, $recipientName)
			->addBcc($this->emailFrom)
			->setSubject($subject)
			->setBody((string)$template)
			->clearHeader('X-Mailer'); // Hide Nette Mailer banner
		if ($cc) {
			$mail->addCc($cc);
		}
		$this->mailer->send($mail);
	}

}
