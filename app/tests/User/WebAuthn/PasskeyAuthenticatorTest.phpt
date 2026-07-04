<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\User\WebAuthn;

use CBOR\ByteStringObject;
use CBOR\MapItem;
use CBOR\MapObject;
use CBOR\TextStringObject;
use Exception;
use MichalSpacekCz\Test\Database\Database;
use MichalSpacekCz\Test\Serializer\SerializerMock;
use MichalSpacekCz\Test\TestCaseRunner;
use MichalSpacekCz\Test\User\WebAuthn\PasskeyAssertionResponseValidatorMock;
use MichalSpacekCz\Test\User\WebAuthn\PasskeyAttestationResponseValidatorMock;
use MichalSpacekCz\User\WebAuthn\Authentication\Exceptions\PasskeyAuthenticationAssertionResponseValidatorException;
use MichalSpacekCz\User\WebAuthn\Authentication\Exceptions\PasskeyAuthenticationCredentialDeserializationException;
use MichalSpacekCz\User\WebAuthn\Authentication\Exceptions\PasskeyAuthenticationCredentialIdTooShortException;
use MichalSpacekCz\User\WebAuthn\Authentication\Exceptions\PasskeyAuthenticationCredentialRecordDeserializationException;
use MichalSpacekCz\User\WebAuthn\Authentication\Exceptions\PasskeyAuthenticationCredentialRecordSerializationException;
use MichalSpacekCz\User\WebAuthn\Authentication\Exceptions\PasskeyAuthenticationCrossOriginAuthenticationException;
use MichalSpacekCz\User\WebAuthn\Authentication\Exceptions\PasskeyAuthenticationInvalidTypeException;
use MichalSpacekCz\User\WebAuthn\Authentication\Exceptions\PasskeyAuthenticationOptionsSerializationException;
use MichalSpacekCz\User\WebAuthn\Authentication\Exceptions\PasskeyAuthenticationUnknownCredentialException;
use MichalSpacekCz\User\WebAuthn\Authentication\Exceptions\PasskeyAuthenticationUserNotFoundException;
use MichalSpacekCz\User\WebAuthn\Exceptions\PasskeyChallengeInvalidException;
use MichalSpacekCz\User\WebAuthn\Exceptions\PasskeyCredentialAlreadyRegisteredException;
use MichalSpacekCz\User\WebAuthn\Registration\Exceptions\PasskeyRegistrationAttestationResponseValidatorException;
use MichalSpacekCz\User\WebAuthn\Registration\Exceptions\PasskeyRegistrationCredentialDeserializationException;
use MichalSpacekCz\User\WebAuthn\Registration\Exceptions\PasskeyRegistrationCredentialIdTooShortException;
use MichalSpacekCz\User\WebAuthn\Registration\Exceptions\PasskeyRegistrationCredentialRecordSerializationException;
use MichalSpacekCz\User\WebAuthn\Registration\Exceptions\PasskeyRegistrationCrossOriginRegistrationException;
use MichalSpacekCz\User\WebAuthn\Registration\Exceptions\PasskeyRegistrationInvalidTypeException;
use MichalSpacekCz\User\WebAuthn\Registration\Exceptions\PasskeyRegistrationOptionsSerializationException;
use MichalSpacekCz\User\WebAuthn\Session\PasskeySessionSection;
use MichalSpacekCz\Utils\Base64;
use Nette\Database\UniqueConstraintViolationException;
use Nette\Security\SimpleIdentity;
use Nette\Security\User;
use Nette\Utils\Json;
use Override;
use Symfony\Component\Serializer\Exception\NotEncodableValueException;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Uid\Uuid;
use Tester\Assert;
use Tester\TestCase;
use Webauthn\AuthenticatorAssertionResponseValidator;
use Webauthn\AuthenticatorAttestationResponseValidator;
use Webauthn\AuthenticatorSelectionCriteria;
use Webauthn\CredentialRecord;
use Webauthn\Exception\AuthenticatorResponseVerificationException;
use Webauthn\PublicKeyCredential;
use Webauthn\PublicKeyCredentialDescriptor;
use Webauthn\TrustPath\EmptyTrustPath;

require __DIR__ . '/../../bootstrap.php';

/** @testCase */
final class PasskeyAuthenticatorTest extends TestCase
{

	private PasskeyAuthenticator $passkeyAuthenticator;


	public function __construct(
		private readonly PasskeySessionSection $passkeySessionSection,
		private readonly Database $database,
		AuthenticatorAttestationResponseValidator $attestationResponseValidator,
		AuthenticatorAssertionResponseValidator $assertionResponseValidator,
		private readonly SerializerInterface $serializer,
		private readonly PasskeyStorage $passkeyStorage,
		private readonly User $user,
	) {
		$this->passkeyAuthenticator = new PasskeyAuthenticator(
			$attestationResponseValidator,
			$assertionResponseValidator,
			$serializer,
			$passkeyStorage,
			$passkeySessionSection,
			'test.example',
			'Test App',
			$user,
		);
	}


	#[Override]
	protected function tearDown(): void
	{
		$this->passkeySessionSection->removeAll();
		$this->database->reset();
	}


	public function testGenerateAuthenticationOptionsGeneratesFreshChallenge(): void
	{
		$options1 = $this->passkeyAuthenticator->generateAuthenticationOptions();
		$options2 = $this->passkeyAuthenticator->generateAuthenticationOptions();
		Assert::notSame($options1, $options2);
	}


	public function testGenerateRegistrationOptionsGeneratesFreshChallenge(): void
	{
		$this->database->setFetchFieldDefaultResult('handle');
		$options1 = $this->passkeyAuthenticator->generateRegistrationOptions(1, 'user', false);
		$options2 = $this->passkeyAuthenticator->generateRegistrationOptions(1, 'user', false);
		Assert::notSame($options1, $options2);
	}


	public function testGenerateRegistrationOptionsExcludesExistingCredentialsOnlyWhenAsked(): void
	{
		$this->database->setFetchFieldDefaultResult('handle');
		$this->database->setFetchPairsDefaultResult([1 => 'existing-credential-id']);

		$without = Json::decode($this->passkeyAuthenticator->generateRegistrationOptions(1, 'user', false), forceArrays: true);
		assert(is_array($without));
		Assert::same([], $without['excludeCredentials'] ?? []);

		$with = Json::decode($this->passkeyAuthenticator->generateRegistrationOptions(1, 'user', true), forceArrays: true);
		assert(is_array($with));
		Assert::notSame([], $with['excludeCredentials'] ?? []);
	}


	public function testVerifyAuthenticationThrowsWhenNoChallengeInSession(): void
	{
		Assert::exception(function (): void {
			$this->passkeyAuthenticator->verifyAuthentication('{}');
		}, PasskeyChallengeInvalidException::class);
	}


	public function testVerifyAuthenticationThrowsOnInvalidCredentialJson(): void
	{
		$credentialRecord = $this->buildCredentialRecord();
		$serializerMock = new SerializerMock([]);
		$serializerMock->willThrow(new NotEncodableValueException());
		$passkeyAuthenticator = $this->createPasskeyAuthenticator(
			new PasskeyAttestationResponseValidatorMock($credentialRecord),
			new PasskeyAssertionResponseValidatorMock($credentialRecord),
			$serializerMock,
		);
		$this->passkeySessionSection->setAuthChallenge(random_bytes(32));
		Assert::exception(function () use ($passkeyAuthenticator): void {
			$passkeyAuthenticator->verifyAuthentication('not-valid-json');
		}, PasskeyAuthenticationCredentialDeserializationException::class);
	}


	public function testVerifyAuthenticationThrowsCrossOrigin(): void
	{
		$this->passkeyAuthenticator->generateAuthenticationOptions();
		Assert::exception(function (): void {
			$this->passkeyAuthenticator->verifyAuthentication($this->buildAssertionCredentialJson(crossOrigin: true));
		}, PasskeyAuthenticationCrossOriginAuthenticationException::class);
	}


	public function testVerifyAuthenticationThrowsCredentialIdTooShort(): void
	{
		$this->passkeyAuthenticator->generateAuthenticationOptions();
		Assert::exception(function (): void {
			$this->passkeyAuthenticator->verifyAuthentication($this->buildAssertionCredentialJson(rawIdBytes: 15));
		}, PasskeyAuthenticationCredentialIdTooShortException::class);
	}


	public function testVerifyAuthenticationThrowsUnknownCredential(): void
	{
		$this->passkeyAuthenticator->generateAuthenticationOptions();
		Assert::exception(function (): void {
			$this->passkeyAuthenticator->verifyAuthentication($this->buildAssertionCredentialJson());
		}, PasskeyAuthenticationUnknownCredentialException::class);
	}


	public function testVerifyAuthenticationThrowsCredentialRecordDeserializationException(): void
	{
		$assertionJson = $this->buildAssertionCredentialJson();
		$credentialRecord = $this->buildCredentialRecord();
		$serializerMock = new SerializerMock([
			PublicKeyCredential::class => $this->serializer->deserialize($assertionJson, PublicKeyCredential::class, 'json'),
		]);
		$serializerMock->willThrow(new NotEncodableValueException());
		$passkeyAuthenticator = $this->createPasskeyAuthenticator(
			new PasskeyAttestationResponseValidatorMock($credentialRecord),
			new PasskeyAssertionResponseValidatorMock($credentialRecord),
			$serializerMock,
		);
		$this->passkeySessionSection->setAuthChallenge(random_bytes(32));
		$this->database->setFetchFieldDefaultResult('serialized-credential');
		Assert::exception(function () use ($passkeyAuthenticator, $assertionJson): void {
			$passkeyAuthenticator->verifyAuthentication($assertionJson);
		}, PasskeyAuthenticationCredentialRecordDeserializationException::class);
	}


	public function testVerifyRegistrationThrowsWhenNoChallengeInSession(): void
	{
		Assert::exception(function (): void {
			$this->passkeyAuthenticator->verifyRegistration('{}', 'foo key', 1);
		}, PasskeyChallengeInvalidException::class);
	}


	public function testVerifyRegistrationThrowsOnInvalidCredentialJson(): void
	{
		$credentialRecord = $this->buildCredentialRecord();
		$serializerMock = new SerializerMock([]);
		$serializerMock->willThrow(new NotEncodableValueException());
		$passkeyAuthenticator = $this->createPasskeyAuthenticator(
			new PasskeyAttestationResponseValidatorMock($credentialRecord),
			new PasskeyAssertionResponseValidatorMock($credentialRecord),
			$serializerMock,
		);
		$this->passkeySessionSection->setRegChallenge(random_bytes(32));
		Assert::exception(function () use ($passkeyAuthenticator): void {
			$passkeyAuthenticator->verifyRegistration('not-valid-json', 'foo key', 1);
		}, PasskeyRegistrationCredentialDeserializationException::class);
	}


	public function testVerifyRegistrationThrowsCrossOrigin(): void
	{
		$this->passkeySessionSection->setRegChallenge(random_bytes(32));
		Assert::exception(function (): void {
			$this->passkeyAuthenticator->verifyRegistration($this->buildAttestationCredentialJson(crossOrigin: true), 'key', 1);
		}, PasskeyRegistrationCrossOriginRegistrationException::class);
	}


	public function testVerifyRegistrationThrowsCredentialIdTooShort(): void
	{
		$this->passkeySessionSection->setRegChallenge(random_bytes(32));
		Assert::exception(function (): void {
			$this->passkeyAuthenticator->verifyRegistration($this->buildAttestationCredentialJson(rawIdBytes: 15), 'key', 1);
		}, PasskeyRegistrationCredentialIdTooShortException::class);
	}


	public function testVerifyAuthenticationThrowsInvalidType(): void
	{
		$this->passkeyAuthenticator->generateAuthenticationOptions();
		Assert::exception(function (): void {
			$this->passkeyAuthenticator->verifyAuthentication($this->buildAttestationCredentialJson());
		}, PasskeyAuthenticationInvalidTypeException::class);
	}


	public function testVerifyAuthenticationThrowsAssertionResponseValidatorException(): void
	{
		$credentialRecord = $this->buildCredentialRecord();
		$assertionMock = new PasskeyAssertionResponseValidatorMock($credentialRecord);
		$assertionMock->willThrow(AuthenticatorResponseVerificationException::create(''));
		$passkeyAuthenticator = $this->createPasskeyAuthenticator(
			new PasskeyAttestationResponseValidatorMock($credentialRecord),
			$assertionMock,
			$this->serializer,
		);
		$passkeyAuthenticator->generateAuthenticationOptions();
		$this->database->setFetchFieldDefaultResult($this->serializer->serialize($credentialRecord, 'json'));
		Assert::exception(function () use ($passkeyAuthenticator): void {
			$passkeyAuthenticator->verifyAuthentication($this->buildAssertionCredentialJson());
		}, PasskeyAuthenticationAssertionResponseValidatorException::class);
	}


	public function testVerifyAuthenticationThrowsUserNotFoundException(): void
	{
		$credentialRecord = $this->buildCredentialRecord();
		$passkeyAuthenticator = $this->createPasskeyAuthenticator(
			new PasskeyAttestationResponseValidatorMock($credentialRecord),
			new PasskeyAssertionResponseValidatorMock($credentialRecord),
			$this->serializer,
		);
		$passkeyAuthenticator->generateAuthenticationOptions();
		$this->database->setFetchFieldDefaultResult($this->serializer->serialize($credentialRecord, 'json'));
		Assert::exception(function () use ($passkeyAuthenticator): void {
			$passkeyAuthenticator->verifyAuthentication($this->buildAssertionCredentialJson());
		}, PasskeyAuthenticationUserNotFoundException::class);
	}


	public function testVerifyAuthenticationReturnsResult(): void
	{
		$credentialRecord = $this->buildCredentialRecord();
		$passkeyAuthenticator = $this->createPasskeyAuthenticator(
			new PasskeyAttestationResponseValidatorMock($credentialRecord),
			new PasskeyAssertionResponseValidatorMock($credentialRecord),
			$this->serializer,
		);
		$passkeyAuthenticator->generateAuthenticationOptions();

		$this->database->setFetchFieldDefaultResult($this->serializer->serialize($credentialRecord, 'json'));
		$this->database->setFetchDefaultResult(['userId' => 42, 'username' => 'test-user', 'credentialName' => 'My Passkey']);

		$result = $passkeyAuthenticator->verifyAuthentication($this->buildAssertionCredentialJson());
		Assert::same(42, $result->userId);
		Assert::same('test-user', $result->username);
	}


	public function testVerifyAssertionDoesNotRecordSignedInCredentialButVerifyAuthenticationDoes(): void
	{
		$this->passkeySessionSection->removeAll();
		$credentialRecord = $this->buildCredentialRecord();
		$passkeyAuthenticator = $this->createPasskeyAuthenticator(
			new PasskeyAttestationResponseValidatorMock($credentialRecord),
			new PasskeyAssertionResponseValidatorMock($credentialRecord),
			$this->serializer,
		);
		$this->database->setFetchFieldDefaultResult($this->serializer->serialize($credentialRecord, 'json'));
		$this->database->setFetchDefaultResult(['userId' => 42, 'username' => 'test-user', 'credentialName' => 'My Passkey']);

		// Reauthentication uses verifyAssertion(), which must not change which passkey counts as the one signed in with
		$passkeyAuthenticator->generateAuthenticationOptions();
		$passkeyAuthenticator->verifyAssertion($this->buildAssertionCredentialJson());
		Assert::null($this->passkeySessionSection->getSignedInCredentialId());

		// Login uses verifyAuthentication(), which records it
		$passkeyAuthenticator->generateAuthenticationOptions();
		$passkeyAuthenticator->verifyAuthentication($this->buildAssertionCredentialJson());
		Assert::notNull($this->passkeySessionSection->getSignedInCredentialId());
	}


	public function testLogoutClearsPasskeySession(): void
	{
		$this->user->login(new SimpleIdentity(1));
		$this->passkeySessionSection->setSignedInCredentialId('some-credential-id');
		$this->passkeySessionSection->setReauthAt(1234567890);
		Assert::same('some-credential-id', $this->passkeySessionSection->getSignedInCredentialId());
		Assert::same(1234567890, $this->passkeySessionSection->getReauthAt());
		$this->user->logout();
		Assert::null($this->passkeySessionSection->getSignedInCredentialId());
		Assert::null($this->passkeySessionSection->getReauthAt());
	}


	public function testVerifyRegistrationThrowsInvalidType(): void
	{
		$this->passkeySessionSection->setRegChallenge(random_bytes(32));
		Assert::exception(function (): void {
			$this->passkeyAuthenticator->verifyRegistration($this->buildAssertionCredentialJson(), 'key', 1);
		}, PasskeyRegistrationInvalidTypeException::class);
	}


	public function testVerifyRegistrationThrowsAttestationResponseValidatorException(): void
	{
		$credentialRecord = $this->buildCredentialRecord();
		$attestationMock = new PasskeyAttestationResponseValidatorMock($credentialRecord);
		$attestationMock->willThrow(new Exception('test'));
		$passkeyAuthenticator = $this->createPasskeyAuthenticator(
			$attestationMock,
			new PasskeyAssertionResponseValidatorMock($credentialRecord),
			$this->serializer,
		);
		$this->passkeySessionSection->setRegChallenge(random_bytes(32));
		$this->database->addFetchFieldResult('handle');
		Assert::exception(function () use ($passkeyAuthenticator): void {
			$passkeyAuthenticator->verifyRegistration($this->buildAttestationCredentialJson(), 'key', 1);
		}, PasskeyRegistrationAttestationResponseValidatorException::class);
	}


	public function testVerifyRegistrationThrowsCredentialAlreadyRegistered(): void
	{
		$credentialRecord = $this->buildCredentialRecord();
		$passkeyAuthenticator = $this->createPasskeyAuthenticator(
			new PasskeyAttestationResponseValidatorMock($credentialRecord),
			new PasskeyAssertionResponseValidatorMock($credentialRecord),
			$this->serializer,
		);
		$this->passkeySessionSection->setRegChallenge(random_bytes(32));
		$this->database->addFetchFieldResult('handle');
		$this->database->setFetchFieldDefaultResult(1); // For PasskeyStorage::saveCredential() to throw PasskeyCredentialAlreadyRegisteredException
		$this->database->willThrow(new UniqueConstraintViolationException());
		Assert::exception(function () use ($passkeyAuthenticator): void {
			$passkeyAuthenticator->verifyRegistration($this->buildAttestationCredentialJson(), 'key', 42);
		}, PasskeyCredentialAlreadyRegisteredException::class);
	}


	public function testGenerateAuthenticationOptionsThrowsSerializationException(): void
	{
		$credentialRecord = $this->buildCredentialRecord();
		$serializerMock = new SerializerMock([]);
		$serializerMock->willThrow(new NotEncodableValueException());
		$passkeyAuthenticator = $this->createPasskeyAuthenticator(
			new PasskeyAttestationResponseValidatorMock($credentialRecord),
			new PasskeyAssertionResponseValidatorMock($credentialRecord),
			$serializerMock,
		);
		Assert::exception(function () use ($passkeyAuthenticator): void {
			$passkeyAuthenticator->generateAuthenticationOptions();
		}, PasskeyAuthenticationOptionsSerializationException::class);
	}


	public function testGenerateRegistrationOptionsThrowsSerializationException(): void
	{
		$credentialRecord = $this->buildCredentialRecord();
		$serializerMock = new SerializerMock([]);
		$serializerMock->willThrow(new NotEncodableValueException());
		$passkeyAuthenticator = $this->createPasskeyAuthenticator(
			new PasskeyAttestationResponseValidatorMock($credentialRecord),
			new PasskeyAssertionResponseValidatorMock($credentialRecord),
			$serializerMock,
		);
		$this->database->setFetchFieldDefaultResult('handle');
		Assert::exception(function () use ($passkeyAuthenticator): void {
			$passkeyAuthenticator->generateRegistrationOptions(1, 'user', false);
		}, PasskeyRegistrationOptionsSerializationException::class);
	}


	public function testVerifyAuthenticationThrowsCredentialRecordSerializationException(): void
	{
		$assertionJson = $this->buildAssertionCredentialJson();
		$credentialRecord = $this->buildCredentialRecord();
		$serializerMock = new SerializerMock([
			PublicKeyCredential::class => $this->serializer->deserialize($assertionJson, PublicKeyCredential::class, 'json'),
			CredentialRecord::class => $credentialRecord,
		]);
		$serializerMock->willThrow(new NotEncodableValueException());
		$passkeyAuthenticator = $this->createPasskeyAuthenticator(
			new PasskeyAttestationResponseValidatorMock($credentialRecord),
			new PasskeyAssertionResponseValidatorMock($credentialRecord),
			$serializerMock,
		);
		$this->passkeySessionSection->setAuthChallenge(random_bytes(32));
		$this->database->setFetchFieldDefaultResult('serialized-credential');
		$this->database->setFetchDefaultResult(['userId' => 42, 'username' => 'test-user', 'credentialName' => 'My Passkey']);
		Assert::exception(function () use ($passkeyAuthenticator, $assertionJson): void {
			$passkeyAuthenticator->verifyAuthentication($assertionJson);
		}, PasskeyAuthenticationCredentialRecordSerializationException::class);
	}


	public function testVerifyRegistrationThrowsCredentialRecordSerializationException(): void
	{
		$attestationJson = $this->buildAttestationCredentialJson();
		$credentialRecord = $this->buildCredentialRecord();
		$serializerMock = new SerializerMock([
			PublicKeyCredential::class => $this->serializer->deserialize($attestationJson, PublicKeyCredential::class, 'json'),
		]);
		$serializerMock->willThrow(new NotEncodableValueException());
		$passkeyAuthenticator = $this->createPasskeyAuthenticator(
			new PasskeyAttestationResponseValidatorMock($credentialRecord),
			new PasskeyAssertionResponseValidatorMock($credentialRecord),
			$serializerMock,
		);
		$this->passkeySessionSection->setRegChallenge(random_bytes(32));
		$this->database->addFetchFieldResult('handle');
		Assert::exception(function () use ($passkeyAuthenticator, $attestationJson): void {
			$passkeyAuthenticator->verifyRegistration($attestationJson, 'key', 42);
		}, PasskeyRegistrationCredentialRecordSerializationException::class);
	}


	public function testVerifyRegistrationSavesCredential(): void
	{
		$credentialRecord = $this->buildCredentialRecord();
		$passkeyAuthenticator = $this->createPasskeyAuthenticator(
			new PasskeyAttestationResponseValidatorMock($credentialRecord),
			new PasskeyAssertionResponseValidatorMock($credentialRecord),
			$this->serializer,
		);
		$this->passkeySessionSection->setRegChallenge(random_bytes(32));
		$this->database->addFetchFieldResult('user-handle');

		$credentialId = $passkeyAuthenticator->verifyRegistration($this->buildAttestationCredentialJson(), 'My Key', 42);
		Assert::same(str_repeat("\x00", 16), $credentialId);

		$params = $this->database->getParamsArrayForQuery('INSERT INTO ?name ?');
		Assert::count(1, $params);
		Assert::same(42, $params[0]['key_user']);
		Assert::same('My Key', $params[0]['name']);
	}


	public function testVerifyRegistrationRequiresUserVerification(): void
	{
		$credentialRecord = $this->buildCredentialRecord();
		$attestationMock = new PasskeyAttestationResponseValidatorMock($credentialRecord);
		$passkeyAuthenticator = $this->createPasskeyAuthenticator(
			$attestationMock,
			new PasskeyAssertionResponseValidatorMock($credentialRecord),
			$this->serializer,
		);
		$this->passkeySessionSection->setRegChallenge(random_bytes(32));
		$this->database->addFetchFieldResult('user-handle');

		$passkeyAuthenticator->verifyRegistration($this->buildAttestationCredentialJson(), 'My Key', 42);

		$options = $attestationMock->getLastCreationOptions();
		Assert::same(
			AuthenticatorSelectionCriteria::USER_VERIFICATION_REQUIREMENT_REQUIRED,
			$options?->authenticatorSelection?->userVerification,
		);
	}


	private function buildCredentialRecord(): CredentialRecord
	{
		return CredentialRecord::create(
			publicKeyCredentialId: str_repeat("\x00", 16),
			type: PublicKeyCredentialDescriptor::CREDENTIAL_TYPE_PUBLIC_KEY,
			transports: [],
			attestationType: 'none',
			trustPath: EmptyTrustPath::create(),
			aaguid: Uuid::fromString('00000000-0000-0000-0000-000000000000'),
			credentialPublicKey: "\x00",
			userHandle: "\x00",
			counter: 0,
		);
	}


	private function createPasskeyAuthenticator(
		PasskeyAttestationResponseValidatorMock $attestationMock,
		PasskeyAssertionResponseValidatorMock $assertionMock,
		SerializerInterface $serializer,
	): PasskeyAuthenticator {
		return new PasskeyAuthenticator(
			$attestationMock,
			$assertionMock,
			$serializer,
			$this->passkeyStorage,
			$this->passkeySessionSection,
			'test.example',
			'Test App',
			$this->user,
		);
	}


	private function buildAssertionCredentialJson(bool $crossOrigin = false, int $rawIdBytes = 16): string
	{
		$idBase64Url = Base64::urlEncode(str_repeat("\x00", $rawIdBytes));
		$clientData = ['type' => 'webauthn.get', 'challenge' => 'AAAA', 'origin' => 'https://test.example'];
		if ($crossOrigin) {
			$clientData['crossOrigin'] = true;
		}
		$clientDataBase64Url = Base64::urlEncode(Json::encode($clientData));
		return Json::encode([
			'id' => $idBase64Url,
			'rawId' => $idBase64Url,
			'type' => 'public-key',
			'response' => [
				'clientDataJSON' => $clientDataBase64Url,
				'authenticatorData' => Base64::urlEncode(str_repeat("\x00", 37)),
				'signature' => 'AAAA',
			],
		]);
	}


	private function buildAttestationCredentialJson(bool $crossOrigin = false, int $rawIdBytes = 16): string
	{
		$idBase64Url = Base64::urlEncode(str_repeat("\x00", $rawIdBytes));
		$clientData = ['type' => 'webauthn.create', 'challenge' => 'AAAA', 'origin' => 'https://test.example'];
		if ($crossOrigin) {
			$clientData['crossOrigin'] = true;
		}
		$clientDataBase64Url = Base64::urlEncode(Json::encode($clientData));
		$cbor = (string) MapObject::create([
			MapItem::create(TextStringObject::create('fmt'), TextStringObject::create('none')),
			MapItem::create(TextStringObject::create('attStmt'), MapObject::create()),
			MapItem::create(TextStringObject::create('authData'), ByteStringObject::create(str_repeat("\x00", 37))),
		]);
		return Json::encode([
			'id' => $idBase64Url,
			'rawId' => $idBase64Url,
			'type' => 'public-key',
			'response' => [
				'clientDataJSON' => $clientDataBase64Url,
				'attestationObject' => Base64::urlEncode($cbor),
			],
		]);
	}

}

TestCaseRunner::run(PasskeyAuthenticatorTest::class);
