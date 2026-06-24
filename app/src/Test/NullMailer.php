<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Test;

use LogicException;
use Nette\Mail\Mailer;
use Nette\Mail\Message;
use Override;

final class NullMailer implements Mailer
{

	use WillThrow;


	/** @var list<Message> */
	private array $allMails = [];


	#[Override]
	public function send(Message $mail): void
	{
		$this->maybeThrow();
		$this->allMails[] = $mail;
	}


	public function getMail(): Message
	{
		if ($this->allMails === []) {
			throw new LogicException('Send mail first with send()');
		}
		return $this->allMails[array_key_last($this->allMails)];
	}


	/**
	 * @return list<Message>
	 */
	public function getAllMails(): array
	{
		return $this->allMails;
	}


	public function reset(): void
	{
		$this->allMails = [];
		$this->wontThrow();
	}

}
