<?php
declare(strict_types = 1);

namespace MichalSpacekCz\User;

use Exception;
use MichalSpacekCz\Database\TypedDatabase;
use MichalSpacekCz\Encryption\EncryptedColumn;
use MichalSpacekCz\Encryption\EncryptedStorage;
use Nette\Database\Explorer;
use Override;
use Spaze\Encryption\SymmetricKeyEncryption;

/**
 * A user's own account settings, with the notification email encrypted at rest like other emails in the app.
 */
final readonly class UserAccounts implements EncryptedStorage
{

	private const string ID_COLUMN = 'id_user';
	private const string NOTIFICATION_EMAIL_COLUMN = 'notification_email';


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
			'SELECT ?name FROM ?name WHERE ?name = ?',
			self::NOTIFICATION_EMAIL_COLUMN,
			$this->usersTableName,
			self::ID_COLUMN,
			$userId,
		);
		return $encrypted !== null ? $this->emailEncryption->decrypt($encrypted) : null;
	}


	public function setNotificationEmail(int $userId, string $email): void
	{
		$this->database->query(
			'UPDATE ?name SET ? WHERE ?name = ?',
			$this->usersTableName,
			[self::NOTIFICATION_EMAIL_COLUMN => $this->emailEncryption->encrypt($email)],
			self::ID_COLUMN,
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
				'SELECT ?name FROM ?name WHERE ?name = ? FOR UPDATE',
				self::NOTIFICATION_EMAIL_COLUMN,
				$this->usersTableName,
				self::ID_COLUMN,
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


	#[Override]
	public function getEncryptedDataLabel(): string
	{
		return 'user notification emails';
	}


	/**
	 * @return non-empty-list<EncryptedColumn>
	 */
	#[Override]
	public function getEncryptedColumns(): array
	{
		return [new EncryptedColumn($this->emailEncryption, $this->usersTableName, self::ID_COLUMN, self::NOTIFICATION_EMAIL_COLUMN)];
	}

}
