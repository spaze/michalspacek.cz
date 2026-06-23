<?php
declare(strict_types = 1);

namespace MichalSpacekCz\User;

use MichalSpacekCz\Database\TypedDatabase;
use Nette\Database\Explorer;
use Spaze\Encryption\SymmetricKeyEncryption;

/**
 * A user's own account settings, with the email encrypted at rest like other emails in the app.
 */
final readonly class UserAccounts
{

	public function __construct(
		private Explorer $database,
		private TypedDatabase $typedDatabase,
		private SymmetricKeyEncryption $emailEncryption,
		private string $usersTableName,
	) {
	}


	public function getEmail(int $userId): ?string
	{
		$encrypted = $this->typedDatabase->fetchFieldStringNullable(
			'SELECT email FROM ?name WHERE id_user = ?',
			$this->usersTableName,
			$userId,
		);
		return $encrypted !== null ? $this->emailEncryption->decrypt($encrypted) : null;
	}


	public function setEmail(int $userId, string $email): void
	{
		$this->database->query(
			'UPDATE ?name SET ? WHERE id_user = ?',
			$this->usersTableName,
			['email' => $this->emailEncryption->encrypt($email)],
			$userId,
		);
	}

}
