<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Presentation\Admin\Account;

use MichalSpacekCz\User\SecurityActivity\SecurityEvent;

final class SecurityLogTemplateParameters
{

	/**
	 * @param list<SecurityEvent> $events
	 */
	public function __construct(
		public string $pageTitle,
		public array $events,
	) {
	}

}
