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

	/** @var \Nette\Mail\IMailer */
	protected $mailer;

	/** @var \MichalSpacekCz\Training\Applications */
	protected $trainingApplications;

	/** @var \MichalSpacekCz\Training\Dates */
	protected $trainingDates;

	/** @var \MichalSpacekCz\Training\Statuses */
	protected $trainingStatuses;

	/** @var \MichalSpacekCz\Training\Venues */
	protected $trainingVenues;

	/** @var \MichalSpacekCz\Training\Files */
	protected $trainingFiles;

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
		Files $trainingFiles
	)
	{
		$this->mailer = $mailer;
		$this->trainingApplications = $trainingApplications;
		$this->trainingDates = $trainingDates;
		$this->trainingStatuses = $trainingStatuses;
		$this->trainingVenues = $trainingVenues;
		$this->trainingFiles = $trainingFiles;
	}


	public function sendSignUpMail($applicationId, \Nette\Bridges\ApplicationLatte\Template $template, $recipientAddress, $recipientName, $start, $training, $trainingName, $venueName, $venueNameExtended, $venueAddress, $venueCity)
	{
		\Nette\Diagnostics\Debugger::log("Sending sign-up email to {$recipientName} <{$recipientAddress}>, application id: {$applicationId}, training: {$training}");

		$template->setFile($this->templatesDir . 'trainingSignUp.latte');

		$template->training     = $training;
		$template->trainingName = $trainingName;
		$template->start        = $start;
		$template->venueName    = $venueName;
		$template->venueNameExtended = $venueNameExtended;
		$template->venueAddress = $venueAddress;
		$template->venueCity    = $venueCity;

		$this->sendMail($recipientAddress, $recipientName, $template);
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
				$applications[] = $application;
			}
		}

		return $applications;
	}


	public function sendInvitation(\Nette\Database\Row $application, \Nette\Bridges\ApplicationLatte\Template $template, $additional = null)
	{
		\Nette\Diagnostics\Debugger::log("Sending invitation email to {$application->name} <{$application->email}>, application id: {$application->id}, training: {$application->trainingAction}");

		$template->setFile($this->templatesDir . 'admin/invitation.latte');
		$template->application = $application;
		$template->additional = $additional;
		$this->sendMail($application->email, $application->name, $template);
	}


	public function sendMaterials(\Nette\Database\Row $application, \Nette\Bridges\ApplicationLatte\Template $template, $additional = null)
	{
		\Nette\Diagnostics\Debugger::log("Sending materials email to {$application->name} <{$application->email}>, application id: {$application->id}, training: {$application->trainingAction}");

		if ($application->familiar) {
			$template->setFile($this->templatesDir . 'admin/materialsFamiliar.latte');
		} else {
			$template->setFile($this->templatesDir . 'admin/materials.latte');
		}
		$template->application = $application;
		$template->additional = $additional;
		$this->sendMail($application->email, $application->name, $template);
	}


	public function sendInvoice(\Nette\Database\Row $application, \Nette\Bridges\ApplicationLatte\Template $template, array $invoice, $additional = null)
	{
		\Nette\Diagnostics\Debugger::log("Sending invoice email to {$application->name} <{$application->email}>, application id: {$application->id}, training: {$application->trainingAction}");

		$template->setFile($this->templatesDir . 'admin/invoice.latte');
		$template->application = $application;
		$template->additional = $additional;
		$this->sendMail($application->email, $application->name, $template, $invoice);
	}


	public function sendReminder(\Nette\Database\Row $application, \Nette\Bridges\ApplicationLatte\Template $template, $additional = null)
	{
		\Nette\Diagnostics\Debugger::log("Sending reminder email to {$application->name} <{$application->email}>, application id: {$application->id}, training: {$application->trainingAction}");

		$template->setFile($this->templatesDir . 'admin/reminder.latte');
		$template->application = $application;
		$template->venue = $this->trainingVenues->get($application->venueAction);
		$template->additional = $additional;
		$this->sendMail($application->email, $application->name, $template);
	}


	private function sendMail($recipientAddress, $recipientName, \Nette\Bridges\ApplicationLatte\Template $template, array $attachments = array())
	{
		$mail = new \Nette\Mail\Message();
		foreach ($attachments as $name => $file) {
			$mail->addAttachment($name, file_get_contents($file));
		}
		$mail->setFrom($this->emailFrom)
			->addTo($recipientAddress, $recipientName)
			->addBcc($this->emailFrom)
			->setBody($template)
			->clearHeader('X-Mailer');  // Hide Nette Mailer banner
		$this->mailer->send($mail);
	}

}
