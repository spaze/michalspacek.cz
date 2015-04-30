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

	const REMINDER_DAYS = 4;

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

	/** @var \Bare\Next\Templating\Helpers */
	protected $bareHelpers;

	/**
	 * Templates directory, ends with a slash.
	 *
	 * @var string
	 */
	protected $templatesDir;


	public function __construct(
		\Nette\Mail\IMailer $mailer,
		Applications $trainingApplications,
		Dates $trainingDates,
		Statuses $trainingStatuses,
		Venues $trainingVenues,
		Files $trainingFiles,
		\Bare\Next\Templating\Helpers $bareHelpers
	)
	{
		$this->mailer = $mailer;
		$this->trainingApplications = $trainingApplications;
		$this->trainingDates = $trainingDates;
		$this->trainingStatuses = $trainingStatuses;
		$this->trainingVenues = $trainingVenues;
		$this->trainingFiles = $trainingFiles;
		$this->bareHelpers = $bareHelpers;
	}


	public function sendSignUpMail($applicationId, \Nette\Bridges\ApplicationLatte\Template $template, $recipientAddress, $recipientName, $start, $training, $trainingName, $venueName, $venueNameExtended, $venueAddress, $venueCity)
	{
		\Tracy\Debugger::log("Sending sign-up email to {$recipientName} <{$recipientAddress}>, application id: {$applicationId}, training: {$training}");

		$template->setFile($this->templatesDir . 'trainingSignUp.latte');

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


	public function setTemplatesDir($dir)
	{
		if ($dir[strlen($dir) - 1] != '/') {
			$dir .= '/';
		}
		$this->templatesDir = $dir;
	}


	public function setEmailFrom($from)
	{
		$this->emailFrom = $from;
	}


	public function getApplications()
	{
		$applications = [];

		foreach ($this->trainingStatuses->getParentStatuses(Statuses::STATUS_INVITED) as $status) {
			foreach ($this->trainingApplications->getByStatus($status) as $application) {
				if ($this->trainingDates->get($application->dateId)->status == Dates::STATUS_CONFIRMED) {
					$applications[] = $application;
				}
			}
		}

		foreach ($this->trainingStatuses->getParentStatuses(Statuses::STATUS_MATERIALS_SENT) as $status) {
			foreach ($this->trainingApplications->getByStatus($status) as $application) {
				$application->files = $this->trainingFiles->getFiles($application->id);
				$applications[] = $application;
			}
		}

		foreach ($this->trainingStatuses->getParentStatuses(Statuses::STATUS_INVOICE_SENT) as $status) {
			foreach ($this->trainingApplications->getByStatus($status) as $application) {
				$applications[] = $application;
			}
		}

		foreach ($this->trainingStatuses->getParentStatuses(Statuses::STATUS_REMINDED) as $status) {
			foreach ($this->trainingApplications->getByStatus($status) as $application) {
				if ($application->trainingStart->diff(new \DateTime('now'))->format('%d') <= self::REMINDER_DAYS) {
					$applications[] = $application;
				}
			}
		}

		return $applications;
	}


	public function sendInvitation(\Nette\Database\Row $application, \Nette\Bridges\ApplicationLatte\Template $template, $additional = null)
	{
		\Tracy\Debugger::log("Sending invitation email to {$application->name}, application id: {$application->id}, training: {$application->trainingAction}");

		$template->setFile($this->templatesDir . 'admin/invitation.latte');
		$template->application = $application;
		$template->additional = $additional;
		$subject = 'Pozvánka na školení ' . $application->trainingName;
		$this->sendMail($application->email, $application->name, $subject, $template);
	}


	public function sendMaterials(\Nette\Database\Row $application, \Nette\Bridges\ApplicationLatte\Template $template, $additional = null)
	{
		\Tracy\Debugger::log("Sending materials email to {$application->name}, application id: {$application->id}, training: {$application->trainingAction}");

		if ($application->familiar) {
			$template->setFile($this->templatesDir . 'admin/materialsFamiliar.latte');
		} else {
			$template->setFile($this->templatesDir . 'admin/materials.latte');
		}
		$template->application = $application;
		$template->additional = $additional;
		$subject = 'Materiály ze školení ' . $application->trainingName;
		$this->sendMail($application->email, $application->name, $subject, $template);
	}


	public function sendInvoice(\Nette\Database\Row $application, \Nette\Bridges\ApplicationLatte\Template $template, array $invoice, $additional = null)
	{
		\Tracy\Debugger::log("Sending invoice email to {$application->name}, application id: {$application->id}, training: {$application->trainingAction}");

		$template->setFile($this->templatesDir . 'admin/invoice.latte');
		$template->application = $application;
		$template->additional = $additional;
		$subject = 'Potvrzení registrace na školení ' . $application->trainingName . ' a faktura';
		$this->sendMail($application->email, $application->name, $subject, $template, $invoice);
	}


	public function sendReminder(\Nette\Database\Row $application, \Nette\Bridges\ApplicationLatte\Template $template, $additional = null)
	{
		\Tracy\Debugger::log("Sending reminder email to {$application->name}, application id: {$application->id}, training: {$application->trainingAction}");

		$template->setFile($this->templatesDir . 'admin/reminder.latte');
		$template->application = $application;
		$template->venue = $this->trainingVenues->get($application->venueAction);
		$template->additional = $additional;

		$start = $this->bareHelpers->localDate($application->trainingStart, 'cs', 'j. n. Y');
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
