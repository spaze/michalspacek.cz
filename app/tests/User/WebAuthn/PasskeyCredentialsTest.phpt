<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\User\WebAuthn;

use MichalSpacekCz\Test\Database\Database;
use MichalSpacekCz\Test\TestCaseRunner;
use MichalSpacekCz\User\WebAuthn\Exceptions\PasskeyCredentialAlreadyRegisteredException;
use Nette\Database\UniqueConstraintViolationException;
use Override;
use Tester\Assert;
use Tester\TestCase;
use Webauthn\PublicKeyCredentialDescriptor;

require __DIR__ . '/../../bootstrap.php';

/** @testCase */
final class PasskeyCredentialsTest extends TestCase
{

	public function __construct(
		private readonly Database $database,
		private readonly PasskeyCredentials $passkeyCredentials,
	) {
	}


	#[Override]
	protected function tearDown(): void
	{
		$this->database->reset();
	}


	public function testGetUserHandle(): void
	{
		$this->database->setFetchFieldDefaultResult('some-handle');
		Assert::same('some-handle', $this->passkeyCredentials->getUserHandle(42));
	}


	public function testFindCredentialRecordJsonByCredentialIdFound(): void
	{
		$this->database->setFetchFieldDefaultResult('{"credential":"json"}');
		Assert::same('{"credential":"json"}', $this->passkeyCredentials->findCredentialRecordJsonByCredentialId('cred-id'));
	}


	public function testFindCredentialRecordJsonByCredentialIdNotFound(): void
	{
		Assert::null($this->passkeyCredentials->findCredentialRecordJsonByCredentialId('cred-id'));
	}


	public function testSaveCredential(): void
	{
		$this->passkeyCredentials->saveCredential('cred-id', '{}', 'My Key', 42);
		$params = $this->database->getParamsArrayForQuery('INSERT INTO ?name ?');
		Assert::count(1, $params);
		Assert::same(42, $params[0]['key_user']);
		Assert::same('cred-id', $params[0]['credential_id']);
		Assert::same('{}', $params[0]['credential_record']);
		Assert::same('My Key', $params[0]['name']);
	}


	public function testSaveCredentialAlreadyRegistered(): void
	{
		$this->database->willThrow(new UniqueConstraintViolationException());
		Assert::exception(function (): void {
			$this->passkeyCredentials->saveCredential('cred-id', '{}', 'My Key', 42);
		}, PasskeyCredentialAlreadyRegisteredException::class);
	}


	public function testUpdateCredentialAfterAuthentication(): void
	{
		$this->passkeyCredentials->updateCredentialAfterAuthentication('cred-id', '{}');
		Assert::same(
			['passkey_credentials', 'cred-id'],
			$this->database->getParamsForQuery('UPDATE ?name SET ? WHERE credential_id = ?'),
		);
		$params = $this->database->getParamsArrayForQuery('UPDATE ?name SET ? WHERE credential_id = ?');
		Assert::count(1, $params);
		Assert::same('{}', $params[0]['credential_record']);
	}


	public function testGetUserByCredentialIdFound(): void
	{
		$this->database->setFetchDefaultResult(['userId' => 42, 'username' => 'foo']);
		$result = $this->passkeyCredentials->getUserByCredentialId('cred-id');
		assert($result !== null);
		Assert::same(42, $result->id);
		Assert::same('foo', $result->username);
	}


	public function testGetUserByCredentialIdNotFound(): void
	{
		Assert::null($this->passkeyCredentials->getUserByCredentialId('cred-id'));
	}


	public function testGetDescriptorsByUserIdEmpty(): void
	{
		Assert::same([], $this->passkeyCredentials->getDescriptorsByUserId(42));
	}


	public function testGetDescriptorsByUserIdWithCredentials(): void
	{
		$this->database->setFetchPairsDefaultResult([0 => 'cred-id-1', 1 => 'cred-id-2']);
		$result = $this->passkeyCredentials->getDescriptorsByUserId(42);
		Assert::count(2, $result);
		Assert::same(PublicKeyCredentialDescriptor::CREDENTIAL_TYPE_PUBLIC_KEY, $result[0]->type);
		Assert::same('cred-id-1', $result[0]->id);
		Assert::same(PublicKeyCredentialDescriptor::CREDENTIAL_TYPE_PUBLIC_KEY, $result[1]->type);
		Assert::same('cred-id-2', $result[1]->id);
	}

}

TestCaseRunner::run(PasskeyCredentialsTest::class);
