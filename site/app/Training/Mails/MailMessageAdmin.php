<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Training\Mails;

class MailMessageAdmin
{

	public function __construct(
		private readonly string $basename,
		private readonly string $subject,
	) {
	}


	public function getBasename(): string
	{
		return $this->basename;
	}


	public function getFilename(): string
	{
		return sprintf('%s/templates/admin/%s.latte', __DIR__, $this->getBasename());
	}


	public function getSubject(): string
	{
		return $this->subject;
	}

}
