<?php
declare(strict_types = 1);

namespace MichalSpacekCz\User\Notifications;

use Contributte\Translation\Translator;
use MichalSpacekCz\Application\LinkGenerator;
use MichalSpacekCz\DateTime\DateTimeFactory;
use MichalSpacekCz\Templating\DefaultTemplate;
use MichalSpacekCz\Templating\TemplateFactory;
use MichalSpacekCz\User\UserAccounts;
use Nette\Http\IRequest;
use Nette\Mail\Mailer;
use Nette\Mail\Message;
use Throwable;
use Tracy\Debugger;

/**
 * Tells a user out of band when something security-sensitive happens to their account (a passkey is
 * added or reset). Best-effort: a delivery failure is logged but never propagated, so it can't break
 * the action that triggered it. A user with no email set is logged and skipped.
 */
final readonly class UserSecurityNotifier
{

	public function __construct(
		private Mailer $mailer,
		private TemplateFactory $templateFactory,
		private Translator $translator,
		private DateTimeFactory $dateTimeFactory,
		private IRequest $httpRequest,
		private LinkGenerator $linkGenerator,
		private UserAccounts $userAccounts,
		private string $emailFrom,
	) {
	}


	public function passkeyAdded(int $userId, string $credentialName): void
	{
		try {
			$address = $this->userAccounts->getEmail($userId);
			if ($address === null) {
				$this->logNoEmail($userId);
				return;
			}
			$template = $this->createTemplate('passkeyAdded');
			$template->credentialName = $credentialName;
			$template->when = $this->dateTimeFactory->create();
			$template->ipAddress = $this->httpRequest->getRemoteAddress();
			$template->reviewUrl = $this->linkGenerator->link('//:Admin:Passkeys:default');
			$this->send($address, 'messages.notifications.passkeyAdded.subject', $template);
		} catch (Throwable $e) {
			Debugger::log($e, 'auth');
		}
	}


	public function passkeyReset(int $userId, string $credentialName, bool $otherAccessRevoked): void
	{
		try {
			$address = $this->userAccounts->getEmail($userId);
			if ($address === null) {
				$this->logNoEmail($userId);
				return;
			}
			$template = $this->createTemplate('passkeyReset');
			$template->credentialName = $credentialName;
			$template->when = $this->dateTimeFactory->create();
			$template->ipAddress = $this->httpRequest->getRemoteAddress();
			$template->reviewUrl = $this->linkGenerator->link('//:Admin:Passkeys:default');
			$template->otherAccessRevoked = $otherAccessRevoked;
			$this->send($address, 'messages.notifications.passkeyReset.subject', $template);
		} catch (Throwable $e) {
			Debugger::log($e, 'auth');
		}
	}


	private function createTemplate(string $name): DefaultTemplate
	{
		$template = $this->templateFactory->createTemplate();
		$template->setFile(__DIR__ . '/templates/' . $name . '.latte');
		return $template;
	}


	private function send(string $address, string $subjectKey, DefaultTemplate $template): void
	{
		$mail = new Message();
		$mail->setFrom($this->emailFrom)
			->addTo($address)
			->setSubject($this->translator->translate($subjectKey))
			->setBody((string)$template)
			->clearHeader('X-Mailer');
		$this->mailer->send($mail);
	}


	private function logNoEmail(int $userId): void
	{
		Debugger::log("No email set for user {$userId}, skipping security notification", 'auth');
	}

}
