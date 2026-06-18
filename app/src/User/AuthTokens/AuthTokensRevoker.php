<?php
declare(strict_types = 1);

namespace MichalSpacekCz\User\AuthTokens;

use MichalSpacekCz\User\UserAccessRevoker;
use Override;

final readonly class AuthTokensRevoker implements UserAccessRevoker
{

	public function __construct(
		private UserAuthTokens $tokens,
	) {
	}


	#[Override]
	public function revokeForUser(int $userId): void
	{
		$this->tokens->deleteAllTypesForUser($userId);
	}

}
