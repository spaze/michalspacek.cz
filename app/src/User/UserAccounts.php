<?php
declare(strict_types = 1);

namespace MichalSpacekCz\User;

use Exception;
use MichalSpacekCz\Database\TypedDatabase;
use Nette\Database\Explorer;
use Spaze\Encryption\SymmetricKeyEncryption;

/**
 * A user's own account settings, with the notification email encrypted at rest like other emails in the app.
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


	public function getNotificationEmail(int $userId): ?string
	{
		$encrypted = $this->typedDatabase->fetchFieldStringNullable(
			'SELECT notification_email FROM ?name WHERE id_user = ?',
			$this->usersTableName,
			$userId,
		);
		return $encrypted !== null ? $this->emailEncryption->decrypt($encrypted) : null;
	}


	public function setNotificationEmail(int $userId, string $email): void
	{
		$this->database->query(
			'UPDATE ?name SET ? WHERE id_user = ?',
			$this->usersTableName,
			['notification_email' => $this->emailEncryption->encrypt($email)],
			$userId,
		);
	}


	/**
	 * @return string|null The previous notification email
	 */
	public function changeNotificationEmail(int $userId, string $newEmail): ?string
	{
		$this->database->beginTransaction();
		try {
			// lock the row so the old notification email can't change between this read and the write below
			$encryptedOld = $this->typedDatabase->fetchFieldStringNullable(
				'SELECT notification_email FROM ?name WHERE id_user = ? FOR UPDATE',
				$this->usersTableName,
				$userId,
			);
			// decrypt before the write so an undecryptable old value rolls back rather than committing then throwing
			$oldEmail = $encryptedOld !== null ? $this->emailEncryption->decrypt($encryptedOld) : null;
			$this->setNotificationEmail($userId, $newEmail);
			$this->database->commit();
		} catch (Exception $e) {
			$this->database->rollBack();
			throw $e;
		}
		return $oldEmail;
	}

}
