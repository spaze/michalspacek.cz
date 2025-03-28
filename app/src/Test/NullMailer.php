<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Test;

use LogicException;
use Nette\Mail\Mailer;
use Nette\Mail\Message;
use Override;

final class NullMailer implements Mailer
{

	private ?Message $mail = null;


	#[Override]
	public function send(Message $mail): void
	{
		$this->mail = $mail;
	}


	public function getMail(): Message
	{
		if ($this->mail === null) {
			throw new LogicException('Send mail first with send()');
		}
		return $this->mail;
	}

}
