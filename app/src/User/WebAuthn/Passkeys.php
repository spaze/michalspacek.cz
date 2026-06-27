<?php
declare(strict_types = 1);

namespace MichalSpacekCz\User\WebAuthn;

use DateTimeInterface;
use Exception;
use MichalSpacekCz\Database\TypedDatabase;
use MichalSpacekCz\DateTime\DateTimeFactory;
use MichalSpacekCz\User\SecurityActivity\SecurityEventLogger;
use MichalSpacekCz\User\SecurityActivity\SecurityEventType;
use MichalSpacekCz\User\WebAuthn\Exceptions\PasskeyCredentialNotFoundException;
use MichalSpacekCz\User\WebAuthn\Exceptions\PasskeyCredentialSignedInWithException;
use MichalSpacekCz\User\WebAuthn\Session\PasskeySessionSection;
use Nette\Database\Explorer;
use Nette\Security\User;
use Symfony\Component\Uid\Uuid;

final readonly class Passkeys
{

	public function __construct(
		private Explorer $database,
		private TypedDatabase $typedDatabase,
		private DateTimeFactory $dateTimeFactory,
		private User $user,
		private PasskeySessionSection $passkeySessionSection,
		private SecurityEventLogger $securityEventLogger,
		private string $passkeysTableName,
	) {
	}


	/**
	 * @return list<RegisteredPasskey>
	 */
	public function getPasskeys(): array
	{
		$currentCredentialId = $this->passkeySessionSection->getSignedInCredentialId();
		$rows = $this->database->fetchAll(
			'SELECT
				id_passkey AS id,
				credential_id AS credentialId,
				name,
				created,
				created_timezone AS createdTimezone,
				last_used AS lastUsed,
				last_used_timezone AS lastUsedTimezone
			FROM ?name
			WHERE key_user = ?
			ORDER BY last_used DESC,
		 	created DESC',
			$this->passkeysTableName,
			(int)$this->user->getId(),
		);
		$now = $this->dateTimeFactory->create();
		$items = [];
		foreach ($rows as $row) {
			assert(is_string($row->id));
			assert(is_string($row->credentialId));
			assert(is_string($row->name));
			assert($row->created instanceof DateTimeInterface);
			assert(is_string($row->createdTimezone));
			assert($row->lastUsed === null || $row->lastUsed instanceof DateTimeInterface);
			assert($row->lastUsedTimezone === null || is_string($row->lastUsedTimezone));
			$lastUsedAt = $row->lastUsed !== null && $row->lastUsedTimezone !== null
				? $this->dateTimeFactory->createFrom($row->lastUsed, $row->lastUsedTimezone)
				: null;
			$items[] = new RegisteredPasskey(
				Uuid::fromBinary($row->id)->toRfc4122(),
				$row->name,
				$this->dateTimeFactory->createFrom($row->created, $row->createdTimezone),
				$lastUsedAt,
				$now,
				$currentCredentialId !== null && $row->credentialId === $currentCredentialId,
			);
		}
		return $items;
	}


	/**
	 * @throws PasskeyCredentialNotFoundException
	 */
	public function getCredentialNameById(Uuid $id): string
	{
		$name = $this->typedDatabase->fetchFieldStringNullable(
			'SELECT name FROM ?name WHERE id_passkey = ? AND key_user = ?',
			$this->passkeysTableName,
			$id->toBinary(),
			(int)$this->user->getId(),
		);
		if ($name === null) {
			throw new PasskeyCredentialNotFoundException();
		}
		return $name;
	}


	/**
	 * @throws PasskeyCredentialNotFoundException
	 */
	public function renameCredential(Uuid $id, string $name): void
	{
		$userId = (int)$this->user->getId();
		$found = true;
		$this->database->beginTransaction();
		try {
			$renamed = $this->database->query(
				'UPDATE ?name SET name = ? WHERE id_passkey = ? AND key_user = ?',
				$this->passkeysTableName,
				$name,
				$id->toBinary(),
				$userId,
			)->getRowCount() !== 0;
			if (!$renamed) {
				$exists = $this->typedDatabase->fetchFieldIntNullable(
					'SELECT 1 FROM ?name WHERE id_passkey = ? AND key_user = ?',
					$this->passkeysTableName,
					$id->toBinary(),
					$userId,
				);
				if ($exists === null) {
					$found = false;
				}
			}
			$this->database->commit();
		} catch (Exception $e) {
			$this->database->rollBack();
			throw $e;
		}
		if (!$found) {
			throw new PasskeyCredentialNotFoundException();
		}
		if ($renamed) {
			$this->securityEventLogger->record($userId, SecurityEventType::PasskeyRenamed, ['passkey' => $name]);
		}
	}


	/**
	 * @throws PasskeyCredentialNotFoundException
	 * @throws PasskeyCredentialSignedInWithException
	 */
	public function deleteCredential(Uuid $id): void
	{
		$name = $this->getCredentialNameById($id);
		$idBinary = $id->toBinary();
		$currentCredentialId = $this->passkeySessionSection->getSignedInCredentialId();
		$affected = $this->database->query(
			'DELETE FROM ?name WHERE id_passkey = ? AND key_user = ? AND (? IS NULL OR credential_id != ?)',
			$this->passkeysTableName,
			$idBinary,
			(int)$this->user->getId(),
			$currentCredentialId,
			$currentCredentialId,
		)->getRowCount();
		if ($affected === 0) {
			// found above, so a zero-row delete is either the signed-in credential (excluded) or a concurrent delete
			$exists = $this->typedDatabase->fetchFieldIntNullable(
				'SELECT 1 FROM ?name WHERE id_passkey = ? AND key_user = ?',
				$this->passkeysTableName,
				$idBinary,
				(int)$this->user->getId(),
			);
			if ($exists !== null) {
				throw new PasskeyCredentialSignedInWithException();
			} else {
				throw new PasskeyCredentialNotFoundException();
			}
		}
		$this->securityEventLogger->record((int)$this->user->getId(), SecurityEventType::PasskeyDeleted, ['passkey' => $name]);
	}

}
