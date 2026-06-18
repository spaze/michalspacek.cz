<?php
declare(strict_types = 1);

namespace MichalSpacekCz\User;

interface UserAccessRevoker
{

	public function revokeForUser(int $userId): void;

}
