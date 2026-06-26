<?php
declare(strict_types = 1);

namespace MichalSpacekCz\User;

use Exception;
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


	/**
	 * @return string|null The previous email
	 */
	public function changeEmail(int $userId, string $newEmail): ?string
	{
		$this->database->beginTransaction();
		try {
			// lock the row so the old email can't change between this read and the write below
			$encryptedOld = $this->typedDatabase->fetchFieldStringNullable(
				'SELECT email FROM ?name WHERE id_user = ? FOR UPDATE',
				$this->usersTableName,
				$userId,
			);
			// decrypt before the write so an undecryptable old value rolls back rather than committing then throwing
			$oldEmail = $encryptedOld !== null ? $this->emailEncryption->decrypt($encryptedOld) : null;
			$this->setEmail($userId, $newEmail);
			$this->database->commit();
		} catch (Exception $e) {
			$this->database->rollBack();
			throw $e;
		}
		return $oldEmail;
	}

}
