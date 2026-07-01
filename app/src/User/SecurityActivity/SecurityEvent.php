<?php
declare(strict_types = 1);

namespace MichalSpacekCz\User\SecurityActivity;

use DateTimeImmutable;

/**
 * `type` is null when an older row's stored action is no longer a known SecurityEventType; `action` always
 * holds the raw stored value, so an unknown one still renders instead of throwing.
 */
final readonly class SecurityEvent
{

	/**
	 * @param array<string, string|null> $details
	 */
	public function __construct(
		public ?SecurityEventType $type,
		public string $action,
		public DateTimeImmutable $created,
		public ?string $ip,
		public ?string $userAgent,
		public array $details,
	) {
	}


	public function labelKey(): string
	{
		return $this->type?->labelKey() ?? 'messages.account.securityLog.event.unknown';
	}

}
