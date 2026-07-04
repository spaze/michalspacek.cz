<?php
declare(strict_types = 1);

namespace MichalSpacekCz\User\SecurityActivity;

use DateTimeInterface;
use MichalSpacekCz\DateTime\DateTimeFactory;
use MichalSpacekCz\User\Exceptions\IdentityIdNotIntException;
use MichalSpacekCz\User\Manager;
use Nette\Database\Explorer;
use Nette\Security\User;
use Nette\Utils\Json;
use Spaze\Encryption\SymmetricKeyEncryption;
use Throwable;
use Tracy\Debugger;

final readonly class SecurityActivity
{

	private const string SELECT = 'SELECT
			action,
			created,
			created_timezone AS createdTimezone,
			ip,
			user_agent AS userAgent,
			details
		FROM security_events';


	public function __construct(
		private Explorer $database,
		private DateTimeFactory $dateTimeFactory,
		private User $user,
		private Manager $manager,
		private SymmetricKeyEncryption $securityEventEncryption,
	) {
	}


	/**
	 * @return list<SecurityEvent>
	 * @throws IdentityIdNotIntException
	 */
	public function getEventsForCurrentUser(): array
	{
		return $this->buildEvents($this->database->fetchAll(
			self::SELECT . ' WHERE key_user = ? ORDER BY created DESC, id_security_event DESC',
			$this->manager->getUserId($this->user),
		));
	}


	/**
	 * @param iterable<\Nette\Database\Row> $rows
	 * @return list<SecurityEvent>
	 */
	private function buildEvents(iterable $rows): array
	{
		$events = [];
		foreach ($rows as $row) {
			try {
				assert(is_string($row->action));
				assert($row->created instanceof DateTimeInterface);
				assert(is_string($row->createdTimezone));
				assert($row->ip === null || is_string($row->ip));
				assert($row->userAgent === null || is_string($row->userAgent));
				assert($row->details === null || is_string($row->details));
				$events[] = new SecurityEvent(
					SecurityEventType::tryFrom($row->action),
					$row->action,
					$this->dateTimeFactory->createFrom($row->created, $row->createdTimezone),
					$row->ip,
					$row->userAgent,
					$row->details !== null ? $this->decodeDetails($row->details) : [],
				);
			} catch (Throwable $e) {
				// one structurally bad row (e.g. an invalid stored timezone) must not take down the whole log
				Debugger::log($e, 'auth');
			}
		}
		return $events;
	}


	/**
	 * @return array<string, string|null>
	 */
	private function decodeDetails(string $encrypted): array
	{
		try {
			$details = [];
			foreach ((array)Json::decode($this->securityEventEncryption->decrypt($encrypted)) as $key => $value) {
				$details[(string)$key] = is_string($value) ? $value : null;
			}
			return $details;
		} catch (Throwable $e) {
			// One row that can't be decrypted (a dropped key, corruption) must not take down the whole log
			Debugger::log($e, 'auth');
			return [];
		}
	}

}
