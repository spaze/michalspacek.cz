<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Training;

use DateTime;
use Nette\Bridges\ApplicationLatte\Template;
use Nette\Database\Row;
use Nette\Http\FileUpload;
use Nette\Mail\Mailer;
use Nette\Mail\Message;
use Nette\Utils\Html;
use Netxten\Templating\Helpers;
use Tracy\Debugger;

class Mails
{

	private const REMINDER_DAYS = 5;

	private Mailer $mailer;

	private Applications $trainingApplications;

	private Dates $trainingDates;

	private Statuses $trainingStatuses;

	private Venues $trainingVenues;

	private Files $trainingFiles;

	private Helpers $netxtenHelpers;

	private string $emailFrom;

	private string $phoneNumber;


	public function __construct(
		Mailer $mailer,
		Applications $trainingApplications,
		Dates $trainingDates,
		Statuses $trainingStatuses,
		Venues $trainingVenues,
		Files $trainingFiles,
		Helpers $netxtenHelpers
	) {
		$this->mailer = $mailer;
		$this->trainingApplications = $trainingApplications;
		$this->trainingDates = $trainingDates;
		$this->trainingStatuses = $trainingStatuses;
		$this->trainingVenues = $trainingVenues;
		$this->trainingFiles = $trainingFiles;
		$this->netxtenHelpers = $netxtenHelpers;
	}


	/**
	 * @param integer $applicationId
	 * @param Template $template
	 * @param string $recipientAddress
	 * @param string $recipientName
	 * @param DateTime $start
	 * @param DateTime $end
	 * @param string $training
	 * @param Html<Html|string> $trainingName
	 * @param bool $remote
	 * @param string $venueName
	 * @param string|null $venueNameExtended
	 * @param string $venueAddress
	 * @param string $venueCity
	 */
	public function sendSignUpMail(
		int $applicationId,
		Template $template,
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
		?string $venueCity
	): void {
		Debugger::log("Sending sign-up email to application id: {$applicationId}, training: {$training}");

		$template->setFile(__DIR__ . '/mails/trainingSignUp.latte');

		$template->training     = $training;
		$template->trainingName = $trainingName;
		$template->start        = $start;
		$template->end          = $end;
		$template->remote = $remote;
		$template->venueName    = $venueName;
		$template->venueNameExtended = $venueNameExtended;
		$template->venueAddress = $venueAddress;
		$template->venueCity    = $venueCity;

		$subject = 'Potvrzení registrace na školení ' . $trainingName;
		$this->sendMail($recipientAddress, $recipientName, $subject, $template);
	}


	public function setEmailFrom(string $from): void
	{
		$this->emailFrom = $from;
	}


	public function setPhoneNumber(string $number): void
	{
		$this->phoneNumber = $number;
	}


	/**
	 * @return Row[]
	 */
	public function getApplications(): array
	{
		$applications = [];

		foreach ($this->trainingApplications->getPreliminaryWithDateSet() as $application) {
			$application->nextStatus = Statuses::STATUS_INVITED;
			$applications[$application->id] = $application;
		}

		foreach ($this->trainingStatuses->getParentStatuses(Statuses::STATUS_INVITED) as $status) {
			foreach ($this->trainingApplications->getByStatus($status) as $application) {
				if ($this->trainingDates->get($application->dateId)->status == Dates::STATUS_CONFIRMED) {
					$application->nextStatus = Statuses::STATUS_INVITED;
					$applications[$application->id] = $application;
				}
			}
		}

		foreach ($this->trainingStatuses->getParentStatuses(Statuses::STATUS_MATERIALS_SENT) as $status) {
			foreach ($this->trainingApplications->getByStatus($status) as $application) {
				if ($status !== Statuses::STATUS_ATTENDED || !$this->trainingStatuses->sendInvoiceAfter($application->id)) {
					$application->files = $this->trainingFiles->getFiles($application->id);
					$application->nextStatus = Statuses::STATUS_MATERIALS_SENT;
					$applications[$application->id] = $application;
				}
			}
		}

		foreach ($this->trainingStatuses->getParentStatuses(Statuses::STATUS_INVOICE_SENT) as $status) {
			foreach ($this->trainingApplications->getByStatus($status) as $application) {
				$application->nextStatus = Statuses::STATUS_INVOICE_SENT;
				$applications[$application->id] = $application;
			}
		}

		foreach ($this->trainingStatuses->getParentStatuses(Statuses::STATUS_INVOICE_SENT_AFTER) as $status) {
			foreach ($this->trainingApplications->getByStatus($status) as $application) {
				if ($status !== Statuses::STATUS_ATTENDED || $this->trainingStatuses->sendInvoiceAfter($application->id)) {
					$application->nextStatus = Statuses::STATUS_INVOICE_SENT_AFTER;
					$applications[$application->id] = $application;
				}
			}
		}

		foreach ($this->trainingStatuses->getParentStatuses(Statuses::STATUS_REMINDED) as $status) {
			foreach ($this->trainingApplications->getByStatus($status) as $application) {
				if ($application->trainingStart->diff(new DateTime('now'))->days <= self::REMINDER_DAYS) {
					$application->files = $this->trainingFiles->getFiles($application->id);
					$application->nextStatus = Statuses::STATUS_REMINDED;
					$applications[$application->id] = $application;
				}
			}
		}

		return $applications;
	}


	/**
	 * @param Row<mixed> $application
	 * @param Template $template
	 * @param string $additional
	 */
	public function sendInvitation(Row $application, Template $template, string $additional): void
	{
		Debugger::log("Sending invitation email application id: {$application->id}, training: {$application->training->action}");

		$template->setFile(__DIR__ . '/mails/admin/invitation.latte');
		$template->application = $application;
		$template->additional = $additional;
		$subject = 'Pozvánka na školení ' . $application->training->name;
		$this->sendMail($application->email, $application->name, $subject, $template);
	}


	/**
	 * @param Row<mixed> $application
	 * @param Template $template
	 * @param bool $feedbackRequest
	 * @param string $additional
	 */
	public function sendMaterials(Row $application, Template $template, bool $feedbackRequest, string $additional): void
	{
		Debugger::log("Sending materials email application id: {$application->id}, training: {$application->training->action}");

		$template->setFile(__DIR__ . '/mails/admin/' . ($application->familiar ?  'materialsFamiliar.latte' : 'materials.latte'));
		$template->application = $application;
		$template->feedbackRequest = $feedbackRequest;
		$template->additional = $additional;
		$subject = 'Materiály ze školení ' . $application->training->name;
		$this->sendMail($application->email, $application->name, $subject, $template);
	}


	/**
	 * @param Row<mixed> $application
	 * @param Template $template
	 * @param FileUpload $invoice
	 * @param string $additional
	 */
	public function sendInvoice(Row $application, Template $template, FileUpload $invoice, string $additional): void
	{
		Debugger::log("Sending invoice email to application id: {$application->id}, training: {$application->training->action}");

		if ($application->nextStatus === Statuses::STATUS_INVOICE_SENT_AFTER) {
			$filename = ($this->trainingStatuses->historyContainsStatuses([Statuses::STATUS_PRO_FORMA_INVOICE_SENT], $application->id) ? 'invoiceAfterProforma.latte' : 'invoiceAfter.latte');
			$subject = 'Faktura za školení ' . $application->training->name;
		} else {
			$filename = 'invoice.latte';
			$subject = 'Potvrzení registrace na školení ' . $application->training->name . ' a faktura';
		}
		$template->setFile(__DIR__ . '/mails/admin/' . $filename);
		$template->application = $application;
		$template->additional = $additional;
		$this->sendMail($application->email, $application->name, $subject, $template, [$invoice->getUntrustedName() => $invoice->getTemporaryFile()]);
	}


	/**
	 * @param Row<mixed> $application
	 * @param Template $template
	 * @param string $additional
	 */
	public function sendReminder(Row $application, Template $template, string $additional): void
	{
		Debugger::log("Sending reminder email application id: {$application->id}, training: {$application->training->action}");

		if ($application->remote) {
			$template->setFile(__DIR__ . '/mails/admin/reminderRemote.latte');
		} else {
			$template->setFile(__DIR__ . '/mails/admin/reminder.latte');
			$template->venue = $this->trainingVenues->get($application->venueAction);
		}
		$template->application = $application;
		$template->phoneNumber = $this->phoneNumber;
		$template->additional = $additional;

		$start = $this->netxtenHelpers->localeIntervalDay($application->trainingStart, $application->trainingEnd, 'cs_CZ');
		$subject = 'Připomenutí školení ' . $application->training->name . ' ' . $start;
		$this->sendMail($application->email, $application->name, $subject, $template);
	}


	/**
	 * @param string $recipientAddress
	 * @param string $recipientName
	 * @param string $subject
	 * @param Template $template
	 * @param string[] $attachments
	 */
	private function sendMail(string $recipientAddress, string $recipientName, string $subject, Template $template, array $attachments = array()): void
	{
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
			->clearHeader('X-Mailer');  // Hide Nette Mailer banner
		$this->mailer->send($mail);
	}

}
