<?php
namespace MichalSpacekCz\Training;

/**
 * Training mails model.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
class Mails
{

	const REMINDER_DAYS = 5;

	/** @var \Nette\Mail\IMailer */
	protected $mailer;

	/** @var Applications */
	protected $trainingApplications;

	/** @var Dates */
	protected $trainingDates;

	/** @var Statuses */
	protected $trainingStatuses;

	/** @var Venues */
	protected $trainingVenues;

	/** @var Files */
	protected $trainingFiles;

	/** @var \Netxten\Templating\Helpers */
	protected $netxtenHelpers;

	/** @var string */
	protected $emailFrom;

	/** @var string */
	protected $phoneNumber;


	public function __construct(
		\Nette\Mail\IMailer $mailer,
		Applications $trainingApplications,
		Dates $trainingDates,
		Statuses $trainingStatuses,
		Venues $trainingVenues,
		Files $trainingFiles,
		\Netxten\Templating\Helpers $netxtenHelpers
	)
	{
		$this->mailer = $mailer;
		$this->trainingApplications = $trainingApplications;
		$this->trainingDates = $trainingDates;
		$this->trainingStatuses = $trainingStatuses;
		$this->trainingVenues = $trainingVenues;
		$this->trainingFiles = $trainingFiles;
		$this->netxtenHelpers = $netxtenHelpers;
	}


	public function sendSignUpMail($applicationId, \Nette\Bridges\ApplicationLatte\Template $template, $recipientAddress, $recipientName, $start, $training, $trainingName, $venueName, $venueNameExtended, $venueAddress, $venueCity)
	{
		\Tracy\Debugger::log("Sending sign-up email to {$recipientName}, application id: {$applicationId}, training: {$training}");

		$template->setFile(__DIR__ . '/mails/trainingSignUp.latte');

		$template->training     = $training;
		$template->trainingName = $trainingName;
		$template->start        = $start;
		$template->venueName    = $venueName;
		$template->venueNameExtended = $venueNameExtended;
		$template->venueAddress = $venueAddress;
		$template->venueCity    = $venueCity;

		$subject = 'Potvrzení registrace na školení ' . $trainingName;
		$this->sendMail($recipientAddress, $recipientName, $subject, $template);
	}


	public function setEmailFrom($from)
	{
		$this->emailFrom = $from;
	}


	public function setPhoneNumber($number)
	{
		$this->phoneNumber = $number;
	}


	public function getApplications()
	{
		$applications = [];

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
				if ($status !== Statuses::STATUS_ATTENDED || ($status === Statuses::STATUS_ATTENDED && !$this->trainingStatuses->historyContainsStatus(Statuses::STATUS_PAID_AFTER, $application->id))) {
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
				if ($status !== Statuses::STATUS_ATTENDED || ($status === Statuses::STATUS_ATTENDED && $this->trainingStatuses->historyContainsStatus(Statuses::STATUS_PAID_AFTER, $application->id))) {
					$application->nextStatus = Statuses::STATUS_INVOICE_SENT_AFTER;
					$applications[$application->id] = $application;
				}
			}
		}

		foreach ($this->trainingStatuses->getParentStatuses(Statuses::STATUS_REMINDED) as $status) {
			foreach ($this->trainingApplications->getByStatus($status) as $application) {
				if ($application->trainingStart->diff(new \DateTime('now'))->days <= self::REMINDER_DAYS) {
					$application->nextStatus = Statuses::STATUS_REMINDED;
					$applications[$application->id] = $application;
				}
			}
		}

		return $applications;
	}


	public function sendInvitation(\Nette\Database\Row $application, \Nette\Bridges\ApplicationLatte\Template $template, $additional = null)
	{
		\Tracy\Debugger::log("Sending invitation email to {$application->name}, application id: {$application->id}, training: {$application->trainingAction}");

		$template->setFile(__DIR__ . '/mails/admin/invitation.latte');
		$template->application = $application;
		$template->additional = $additional;
		$subject = 'Pozvánka na školení ' . $application->trainingName;
		$this->sendMail($application->email, $application->name, $subject, $template);
	}


	public function sendMaterials(\Nette\Database\Row $application, \Nette\Bridges\ApplicationLatte\Template $template, $additional = null)
	{
		\Tracy\Debugger::log("Sending materials email to {$application->name}, application id: {$application->id}, training: {$application->trainingAction}");

		$template->setFile(__DIR__ . '/mails/admin/' . ($application->familiar ?  'materialsFamiliar.latte' : 'materials.latte'));
		$template->application = $application;
		$template->additional = $additional;
		$subject = 'Materiály ze školení ' . $application->trainingName;
		$this->sendMail($application->email, $application->name, $subject, $template);
	}


	public function sendInvoice(\Nette\Database\Row $application, \Nette\Bridges\ApplicationLatte\Template $template, array $invoice, $additional = null)
	{
		\Tracy\Debugger::log("Sending invoice email to {$application->name}, application id: {$application->id}, training: {$application->trainingAction}");

		$template->setFile(__DIR__ . '/mails/admin/' . ($application->nextStatus === Statuses::STATUS_INVOICE_SENT_AFTER ? 'invoiceAfter.latte' : 'invoice.latte'));
		$template->application = $application;
		$template->additional = $additional;
		$subject = 'Potvrzení registrace na školení ' . $application->trainingName . ' a faktura';
		$this->sendMail($application->email, $application->name, $subject, $template, $invoice);
	}


	public function sendReminder(\Nette\Database\Row $application, \Nette\Bridges\ApplicationLatte\Template $template, $additional = null)
	{
		\Tracy\Debugger::log("Sending reminder email to {$application->name}, application id: {$application->id}, training: {$application->trainingAction}");

		$template->setFile(__DIR__ . '/mails/admin/reminder.latte');
		$template->application = $application;
		$template->venue = $this->trainingVenues->get($application->venueAction);
		$template->phoneNumber = $this->phoneNumber;
		$template->additional = $additional;

		$start = $this->netxtenHelpers->localDate($application->trainingStart, 'cs', 'j. n. Y');
		$subject = 'Připomenutí školení ' . $application->trainingName . ' ' . $start;
		$this->sendMail($application->email, $application->name, $subject, $template);
	}


	private function sendMail($recipientAddress, $recipientName, $subject, \Nette\Bridges\ApplicationLatte\Template $template, array $attachments = array())
	{
		$mail = new \Nette\Mail\Message();
		foreach ($attachments as $name => $file) {
			$mail->addAttachment($name, file_get_contents($file));
		}
		$mail->setFrom($this->emailFrom)
			->addTo($recipientAddress, $recipientName)
			->addBcc($this->emailFrom)
			->setSubject($subject)
			->setBody($template)
			->clearHeader('X-Mailer');  // Hide Nette Mailer banner
		$this->mailer->send($mail);
	}

}
