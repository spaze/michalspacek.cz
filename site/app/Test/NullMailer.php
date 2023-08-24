<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Test;

use Nette\Mail\Mailer;
use Nette\Mail\Message;

class NullMailer implements Mailer
{

	private Message $mail;


	public function send(Message $mail): void
	{
		$this->mail = $mail;
	}


	public function getMail(): Message
	{
		return $this->mail;
	}

}
