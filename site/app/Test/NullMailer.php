<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Test;

use LogicException;
use Nette\Mail\Mailer;
use Nette\Mail\Message;

class NullMailer implements Mailer
{

	private ?Message $mail = null;


	public function send(Message $mail): void
	{
		$this->mail = $mail;
	}


	public function getMail(): Message
	{
		if (!$this->mail) {
			throw new LogicException('Send mail first with send()');
		}
		return $this->mail;
	}

}
