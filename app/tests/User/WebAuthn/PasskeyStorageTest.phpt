<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\User\WebAuthn;

use MichalSpacekCz\Test\Database\Database;
use MichalSpacekCz\Test\Database\ResultSet;
use MichalSpacekCz\Test\TestCaseRunner;
use MichalSpacekCz\User\WebAuthn\Exceptions\PasskeyCredentialAlreadyRegisteredException;
use MichalSpacekCz\User\WebAuthn\Exceptions\PasskeyCredentialToKeepNotFoundException;
use Nette\Database\UniqueConstraintViolationException;
use Override;
use Tester\Assert;
use Tester\TestCase;
use Webauthn\PublicKeyCredentialDescriptor;

require __DIR__ . '/../../bootstrap.php';

/** @testCase */
final class PasskeyStorageTest extends TestCase
{

	public function __construct(
		private readonly Database $database,
		private readonly PasskeyStorage $passkeyStorage,
	) {
	}


	#[Override]
	protected function tearDown(): void
	{
		$this->database->reset();
	}


	public function testGetUserHandleByUserId(): void
	{
		$this->database->setFetchFieldDefaultResult('user-handle-42');
		Assert::same('user-handle-42', $this->passkeyStorage->getUserHandleByUserId(42));
	}


	public function testFindCredentialRecordJsonByCredentialIdFound(): void
	{
		$this->database->setFetchFieldDefaultResult('{"credential":"json"}');
		Assert::same('{"credential":"json"}', $this->passkeyStorage->findCredentialRecordJsonByCredentialId('cred-id'));
	}


	public function testFindCredentialRecordJsonByCredentialIdNotFound(): void
	{
		Assert::null($this->passkeyStorage->findCredentialRecordJsonByCredentialId('cred-id'));
	}


	public function testUpdateCredentialAfterAuthentication(): void
	{
		$this->passkeyStorage->updateCredentialAfterAuthentication('cred-id', '{}');
		Assert::same(
			['passkeys', 'cred-id'],
			$this->database->getParamsForQuery('UPDATE ?name SET ? WHERE credential_id = ?'),
		);
		$params = $this->database->getParamsArrayForQuery('UPDATE ?name SET ? WHERE credential_id = ?');
		Assert::count(1, $params);
		Assert::same('{}', $params[0]['credential_record']);
	}


	public function testGetUserByCredentialIdFound(): void
	{
		$this->database->setFetchDefaultResult(['userId' => 42, 'username' => 'foo']);
		$result = $this->passkeyStorage->getUserByCredentialId('cred-id');
		assert($result !== null);
		Assert::same(42, $result->id);
		Assert::same('foo', $result->username);
	}


	public function testGetUserByCredentialIdNotFound(): void
	{
		Assert::null($this->passkeyStorage->getUserByCredentialId('cred-id'));
	}


	public function testSaveCredential(): void
	{
		$this->passkeyStorage->saveCredential('cred-id', '{}', 'Mike E', 42);
		$params = $this->database->getParamsArrayForQuery('INSERT INTO ?name ?');
		Assert::count(1, $params);
		assert(is_string($params[0]['id_passkey']));
		Assert::same(16, strlen($params[0]['id_passkey']));
		Assert::same(42, $params[0]['key_user']);
		Assert::same('cred-id', $params[0]['credential_id']);
		Assert::same('{}', $params[0]['credential_record']);
		Assert::same('Mike E', $params[0]['name']);
	}


	public function testSaveCredentialAlreadyRegistered(): void
	{
		$this->database->setFetchFieldDefaultResult(1); // For PasskeyStorage::saveCredential() to throw
		$this->database->willThrow(new UniqueConstraintViolationException());
		Assert::exception(function (): void {
			$this->passkeyStorage->saveCredential('cred-id', '{}', 'Mike E', 42);
		}, PasskeyCredentialAlreadyRegisteredException::class);
	}


	public function testDeleteCredentialsByUserIdExcept(): void
	{
		$this->database->setFetchFieldDefaultResult(1); // the credential to keep exists for the user
		$this->database->setResultSet(new ResultSet(2));
		$deleted = $this->passkeyStorage->deleteCredentialsByUserIdExcept(42, 'keep-cred-id');

		Assert::same(2, $deleted);
		Assert::same(
			['passkeys', 42, 'keep-cred-id'],
			$this->database->getParamsForQuery('DELETE FROM ?name WHERE key_user = ? AND credential_id != ?'),
		);
	}


	public function testDeleteCredentialsByUserIdExceptRefusesWhenKeptMissing(): void
	{
		// fetchField default is null, so the credential to keep is not found for the user
		Assert::exception(function (): void {
			$this->passkeyStorage->deleteCredentialsByUserIdExcept(42, 'missing-cred-id');
		}, PasskeyCredentialToKeepNotFoundException::class);
		Assert::same([], $this->database->getParamsForQuery('DELETE FROM ?name WHERE key_user = ? AND credential_id != ?'));
	}


	public function testGetDescriptorsByUserIdEmpty(): void
	{
		Assert::same([], $this->passkeyStorage->getDescriptorsByUserId(42));
	}


	public function testGetDescriptorsByUserIdWithCredentials(): void
	{
		$this->database->setFetchPairsDefaultResult([0 => 'cred-id-1', 1 => 'cred-id-2']);
		$result = $this->passkeyStorage->getDescriptorsByUserId(42);
		Assert::count(2, $result);
		Assert::same(PublicKeyCredentialDescriptor::CREDENTIAL_TYPE_PUBLIC_KEY, $result[0]->type);
		Assert::same('cred-id-1', $result[0]->id);
		Assert::same(PublicKeyCredentialDescriptor::CREDENTIAL_TYPE_PUBLIC_KEY, $result[1]->type);
		Assert::same('cred-id-2', $result[1]->id);
	}

}

TestCaseRunner::run(PasskeyStorageTest::class);
