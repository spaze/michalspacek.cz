<?php
declare(strict_types = 1);

namespace MichalSpacekCz\SecurityTxtValidator\Account;

final readonly class SecurityTxtValidatorAccount
{

	public function __construct(
		private(set) bool $accountFeatureEnabled,
	) {
	}

}
