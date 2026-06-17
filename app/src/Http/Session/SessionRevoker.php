<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Http\Session;

use MichalSpacekCz\User\UserAccessRevoker;
use Nette\Database\Explorer;
use Override;

final readonly class SessionRevoker implements UserAccessRevoker
{

	public function __construct(
		private Explorer $database,
		private string $tableName,
	) {
	}


	#[Override]
	public function revokeForUser(int $userId): void
	{
		$this->database->query('DELETE FROM ?name WHERE key_user = ?', $this->tableName, $userId);
	}

}
