<?php
declare(strict_types = 1);

namespace MichalSpacekCz\User\SecurityActivity;

use MichalSpacekCz\DateTime\DateTimeFactory;
use Nette\Database\Explorer;
use Nette\Http\IRequest;
use Nette\Utils\Json;
use Spaze\Encryption\SymmetricKeyEncryption;
use Throwable;
use Tracy\Debugger;

/**
 * Best-effort like the email notifier: a write failure is logged to 'auth' and swallowed, never propagated,
 * so recording an event can't break the action that triggered it. Works from CLI too, where there's no
 * request and so no IP or user agent.
 *
 * `details` is encrypted at rest because the user can read it back, so it must carry only user-safe data
 * (credential names, the user's own emails), never exception or error messages that could leak internal state.
 */
final readonly class SecurityEventLogger
{

	public function __construct(
		private Explorer $database,
		private DateTimeFactory $dateTimeFactory,
		private IRequest $httpRequest,
		private SymmetricKeyEncryption $securityEventEncryption,
	) {
	}


	/**
	 * @param array<string, string|null> $details User-owned, user-safe data only; NEVER exception/error messages
	 */
	public function record(int $userId, SecurityEventType $type, array $details = []): void
	{
		try {
			$now = $this->dateTimeFactory->create();
			$this->database->query(
				'INSERT INTO security_events',
				[
					'key_user' => $userId,
					'action' => $type->value,
					'created' => $now,
					'created_timezone' => $now->getTimezone()->getName(),
					'ip' => $this->httpRequest->getRemoteAddress(),
					'user_agent' => $this->httpRequest->getHeader('User-Agent'),
					'details' => $details === [] ? null : $this->securityEventEncryption->encrypt(Json::encode($details)),
				],
			);
		} catch (Throwable $e) {
			Debugger::log($e, 'auth');
		}
	}

}
