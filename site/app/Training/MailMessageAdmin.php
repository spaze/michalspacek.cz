<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Training;

class MailMessageAdmin
{

	private string $basename;

	private string $subject;


	public function __construct(string $basename, string $subject)
	{
		$this->basename = $basename;
		$this->subject = $subject;
	}


	public function getBasename(): string
	{
		return $this->basename;
	}


	public function getFilename(): string
	{
		return sprintf('%s/mails/admin/%s.latte', __DIR__, $this->getBasename());
	}


	public function getSubject(): string
	{
		return $this->subject;
	}

}
