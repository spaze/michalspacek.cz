<?php
declare(strict_types = 1);

namespace MichalSpacekCz\User\WebAuthn;

use Exception;
use MichalSpacekCz\Database\TypedDatabase;
use MichalSpacekCz\DateTime\DateTimeFactory;
use MichalSpacekCz\ShouldNotHappenException;
use MichalSpacekCz\User\WebAuthn\Exceptions\PasskeyCredentialAlreadyRegisteredException;
use MichalSpacekCz\User\WebAuthn\Exceptions\PasskeyCredentialToKeepNotFoundException;
use Nette\Database\Explorer;
use Nette\Database\UniqueConstraintViolationException;
use Nette\Utils\DateTime;
use Symfony\Component\Uid\UuidV7;
use Webauthn\PublicKeyCredentialDescriptor;

final readonly class PasskeyStorage
{

	public function __construct(
		private Explorer $database,
		private TypedDatabase $typedDatabase,
		private DateTimeFactory $dateTimeFactory,
		private string $passkeysTableName,
		private string $usersTableName,
	) {
	}


	public function getUserHandleByUserId(int $userId): string
	{
		return $this->typedDatabase->fetchFieldString(
			'SELECT passkey_user_handle FROM ?name WHERE id_user = ?',
			$this->usersTableName,
			$userId,
		);
	}


	public function findCredentialRecordJsonByCredentialId(string $credentialId): ?string
	{
		return $this->typedDatabase->fetchFieldStringNullable(
			'SELECT credential_record FROM ?name WHERE credential_id = ?',
			$this->passkeysTableName,
			$credentialId,
		);
	}


	public function updateCredentialAfterAuthentication(string $credentialId, string $credentialRecordJson): void
	{
		$now = $this->dateTimeFactory->create();
		$this->database->query(
			'UPDATE ?name SET ? WHERE credential_id = ?',
			$this->passkeysTableName,
			[
				'credential_record' => $credentialRecordJson,
				'last_used' => DateTime::from($now),
				'last_used_timezone' => $now->getTimezone()->getName(),
			],
			$credentialId,
		);
	}


	public function getUserByCredentialId(string $credentialId): ?PasskeyUser
	{
		$result = $this->database->fetch(
			'SELECT u.id_user AS userId, u.username, c.name AS credentialName FROM ?name c JOIN ?name u ON c.key_user = u.id_user WHERE credential_id = ?',
			$this->passkeysTableName,
			$this->usersTableName,
			$credentialId,
		);
		if ($result === null) {
			return null;
		}
		assert(is_int($result->userId));
		assert(is_string($result->username));
		assert(is_string($result->credentialName));
		return new PasskeyUser($result->userId, $result->username, $result->credentialName);
	}


	/**
	 * @throws PasskeyCredentialAlreadyRegisteredException
	 */
	public function saveCredential(string $credentialId, string $credentialRecordJson, string $name, int $userId): void
	{
		$now = $this->dateTimeFactory->create();
		for ($attempt = 0; $attempt < 3; $attempt++) {
			$this->database->beginTransaction();
			try {
				$this->database->query(
					'INSERT INTO ?name ?',
					$this->passkeysTableName,
					[
						'id_passkey' => new UuidV7()->toBinary(),
						'key_user' => $userId,
						'credential_id' => $credentialId,
						'credential_record' => $credentialRecordJson,
						'name' => $name,
						'created' => DateTime::from($now),
						'created_timezone' => $now->getTimezone()->getName(),
					],
				);
				$this->database->commit();
				return;
			} catch (UniqueConstraintViolationException $e) {
				try {
					$exists = $this->typedDatabase->fetchFieldIntNullable(
						'SELECT 1 FROM ?name WHERE credential_id = ?',
						$this->passkeysTableName,
						$credentialId,
					);
				} finally {
					$this->database->rollBack();
				}
				if ($exists !== null) {
					throw new PasskeyCredentialAlreadyRegisteredException(previous: $e);
				}
			} catch (Exception $e) {
				$this->database->rollBack();
				throw $e;
			}
		}
		throw new ShouldNotHappenException('Failed to generate a unique passkey id after 3 attempts');
	}


	/**
	 * Revoke every passkey the user has except the freshly registered one, for the logged-out
	 * reset. Throws when the kept credential is not the user's, because "everything except it"
	 * with a wrong id would empty the account to zero passkeys, which has to fail loudly rather
	 * than quietly delete them all.
	 *
	 * @return int How many passkeys were deleted
	 * @throws PasskeyCredentialToKeepNotFoundException
	 */
	public function deleteCredentialsByUserIdExcept(int $userId, string $keepCredentialId): int
	{
		$this->database->beginTransaction();
		try {
			// Lock the kept row so a concurrent delete of it can't slip in before the delete
			// below and leave the user at zero passkeys (FOR UPDATE is just the exclusive-lock
			// read; a DELETE takes the same lock, there is no FOR DELETE).
			$keptExists = $this->typedDatabase->fetchFieldIntNullable(
				'SELECT 1 FROM ?name WHERE key_user = ? AND credential_id = ? FOR UPDATE',
				$this->passkeysTableName,
				$userId,
				$keepCredentialId,
			) !== null;
			if ($keptExists) {
				$deleted = $this->database->query(
					'DELETE FROM ?name WHERE key_user = ? AND credential_id != ?',
					$this->passkeysTableName,
					$userId,
					$keepCredentialId,
				)->getRowCount() ?? 0;
				$this->database->commit();
				return $deleted;
			}
			$this->database->commit();
		} catch (Exception $e) {
			$this->database->rollBack();
			throw $e;
		}
		throw new PasskeyCredentialToKeepNotFoundException();
	}


	/**
	 * @return list<PublicKeyCredentialDescriptor>
	 */
	public function getDescriptorsByUserId(int $userId): array
	{
		$credentialIds = $this->typedDatabase->fetchPairsIntString(
			'SELECT credential_id FROM ?name WHERE key_user = ?',
			$this->passkeysTableName,
			$userId,
		);
		$descriptors = [];
		foreach ($credentialIds as $credentialId) {
			$descriptors[] = PublicKeyCredentialDescriptor::create(PublicKeyCredentialDescriptor::CREDENTIAL_TYPE_PUBLIC_KEY, $credentialId);
		}
		return $descriptors;
	}

}
