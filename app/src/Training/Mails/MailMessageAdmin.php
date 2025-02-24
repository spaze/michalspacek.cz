<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Training\Mails;

final readonly class MailMessageAdmin
{

	public function __construct(
		private string $basename,
		private string $subject,
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
