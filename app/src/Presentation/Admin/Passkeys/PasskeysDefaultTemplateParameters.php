<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Presentation\Admin\Passkeys;

use MichalSpacekCz\User\WebAuthn\RegisteredPasskey;

final class PasskeysDefaultTemplateParameters
{

	/**
	 * @param list<RegisteredPasskey> $passkeys
	 */
	public function __construct(
		public string $pageTitle,
		public array $passkeys,
	) {
	}

}
