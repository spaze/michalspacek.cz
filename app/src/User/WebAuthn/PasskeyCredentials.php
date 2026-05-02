<?php
declare(strict_types = 1);

namespace MichalSpacekCz\User\WebAuthn;

use MichalSpacekCz\Database\TypedDatabase;
use MichalSpacekCz\DateTime\DateTimeFactory;
use MichalSpacekCz\User\WebAuthn\Exceptions\PasskeyCredentialAlreadyRegisteredException;
use Nette\Database\Explorer;
use Nette\Database\UniqueConstraintViolationException;
use Nette\Utils\DateTime;
use Webauthn\PublicKeyCredentialDescriptor;

final readonly class PasskeyCredentials
{

	public function __construct(
		private Explorer $database,
		private TypedDatabase $typedDatabase,
		private DateTimeFactory $dateTimeFactory,
		private string $passkeyCredentialsTableName,
		private string $usersTableName,
	) {
	}


	public function getUserHandle(int $userId): string
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
			$this->passkeyCredentialsTableName,
			$credentialId,
		);
	}


	/**
	 * @throws PasskeyCredentialAlreadyRegisteredException
	 */
	public function saveCredential(string $credentialId, string $credentialRecordJson, string $name, int $userId): void
	{
		$now = $this->dateTimeFactory->create();
		try {
			$this->database->query(
				'INSERT INTO ?name ?',
				$this->passkeyCredentialsTableName,
				[
					'key_user' => $userId,
					'credential_id' => $credentialId,
					'credential_record' => $credentialRecordJson,
					'name' => $name,
					'created' => DateTime::from($now),
					'created_timezone' => $now->getTimezone()->getName(),
				],
			);
		} catch (UniqueConstraintViolationException $e) {
			throw new PasskeyCredentialAlreadyRegisteredException(previous: $e);
		}
	}


	public function updateCredentialAfterAuthentication(string $credentialId, string $credentialRecordJson): void
	{
		$now = $this->dateTimeFactory->create();
		$this->database->query(
			'UPDATE ?name SET ? WHERE credential_id = ?',
			$this->passkeyCredentialsTableName,
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
			'SELECT u.id_user AS userId, u.username FROM ?name c JOIN ?name u ON c.key_user = u.id_user WHERE credential_id = ?',
			$this->passkeyCredentialsTableName,
			$this->usersTableName,
			$credentialId,
		);
		if ($result === null) {
			return null;
		}
		assert(is_int($result->userId));
		assert(is_string($result->username));
		return new PasskeyUser($result->userId, $result->username);
	}


	/**
	 * @return list<PublicKeyCredentialDescriptor>
	 */
	public function getDescriptorsByUserId(int $userId): array
	{
		$credentialIds = $this->typedDatabase->fetchPairsIntString(
			'SELECT credential_id FROM ?name WHERE key_user = ?',
			$this->passkeyCredentialsTableName,
			$userId,
		);
		$descriptors = [];
		foreach ($credentialIds as $credentialId) {
			$descriptors[] = PublicKeyCredentialDescriptor::create(PublicKeyCredentialDescriptor::CREDENTIAL_TYPE_PUBLIC_KEY, $credentialId);
		}
		return $descriptors;
	}

}
