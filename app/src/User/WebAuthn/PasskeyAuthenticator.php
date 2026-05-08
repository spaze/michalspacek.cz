<?php
declare(strict_types = 1);

namespace MichalSpacekCz\User\WebAuthn;

use Cose\Algorithms;
use MichalSpacekCz\User\WebAuthn\Exceptions\PasskeyAuthenticationAssertionResponseValidatorException;
use MichalSpacekCz\User\WebAuthn\Exceptions\PasskeyAuthenticationCredentialDeserializationException;
use MichalSpacekCz\User\WebAuthn\Exceptions\PasskeyAuthenticationCredentialIdTooShortException;
use MichalSpacekCz\User\WebAuthn\Exceptions\PasskeyAuthenticationCredentialRecordDeserializationException;
use MichalSpacekCz\User\WebAuthn\Exceptions\PasskeyAuthenticationCredentialRecordSerializationException;
use MichalSpacekCz\User\WebAuthn\Exceptions\PasskeyAuthenticationCrossOriginAuthenticationException;
use MichalSpacekCz\User\WebAuthn\Exceptions\PasskeyAuthenticationInvalidTypeException;
use MichalSpacekCz\User\WebAuthn\Exceptions\PasskeyAuthenticationOptionsSerializationException;
use MichalSpacekCz\User\WebAuthn\Exceptions\PasskeyAuthenticationUnknownCredentialException;
use MichalSpacekCz\User\WebAuthn\Exceptions\PasskeyAuthenticationUserNotFoundException;
use MichalSpacekCz\User\WebAuthn\Exceptions\PasskeyChallengeInvalidException;
use MichalSpacekCz\User\WebAuthn\Exceptions\PasskeyCredentialAlreadyRegisteredException;
use MichalSpacekCz\User\WebAuthn\Exceptions\PasskeyRegistrationAttestationResponseValidatorException;
use MichalSpacekCz\User\WebAuthn\Exceptions\PasskeyRegistrationCredentialDeserializationException;
use MichalSpacekCz\User\WebAuthn\Exceptions\PasskeyRegistrationCredentialIdTooShortException;
use MichalSpacekCz\User\WebAuthn\Exceptions\PasskeyRegistrationCredentialRecordSerializationException;
use MichalSpacekCz\User\WebAuthn\Exceptions\PasskeyRegistrationCrossOriginRegistrationException;
use MichalSpacekCz\User\WebAuthn\Exceptions\PasskeyRegistrationInvalidTypeException;
use MichalSpacekCz\User\WebAuthn\Exceptions\PasskeyRegistrationOptionsSerializationException;
use Nette\Http\Session;
use Nette\Http\SessionSection;
use Override;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Throwable;
use Webauthn\AuthenticatorAssertionResponse;
use Webauthn\AuthenticatorAssertionResponseValidator;
use Webauthn\AuthenticatorAttestationResponse;
use Webauthn\AuthenticatorAttestationResponseValidator;
use Webauthn\AuthenticatorSelectionCriteria;
use Webauthn\CredentialRecord;
use Webauthn\Exception\AuthenticatorResponseVerificationException;
use Webauthn\PublicKeyCredential;
use Webauthn\PublicKeyCredentialCreationOptions;
use Webauthn\PublicKeyCredentialParameters;
use Webauthn\PublicKeyCredentialRequestOptions;
use Webauthn\PublicKeyCredentialRpEntity;
use Webauthn\PublicKeyCredentialUserEntity;

final readonly class PasskeyAuthenticator implements WebAuthnAuthenticator
{

	private const string SESSION_AUTH_CHALLENGE = 'authChallenge';
	private const string SESSION_REG_CHALLENGE = 'regChallenge';


	public function __construct(
		private AuthenticatorAttestationResponseValidator $attestationResponseValidator,
		private AuthenticatorAssertionResponseValidator $assertionResponseValidator,
		private SerializerInterface $serializer,
		private PasskeyCredentials $credentials,
		private Session $session,
		private string $rpId,
		private string $rpName,
	) {
	}


	/**
	 * @throws PasskeyRegistrationOptionsSerializationException
	 * @phpstan-impure
	 */
	#[Override]
	public function generateRegistrationOptions(int $userId, string $username): string
	{
		$rp = PublicKeyCredentialRpEntity::create($this->rpName, $this->rpId);
		$userHandle = $this->credentials->getUserHandle($userId);
		$user = PublicKeyCredentialUserEntity::create($username, $userHandle, $username);
		$options = PublicKeyCredentialCreationOptions::create(
			$rp,
			$user,
			$this->createChallenge(self::SESSION_REG_CHALLENGE),
			$this->getPubKeyCredParams(),
			AuthenticatorSelectionCriteria::create(
				userVerification: AuthenticatorSelectionCriteria::USER_VERIFICATION_REQUIREMENT_REQUIRED,
				residentKey: AuthenticatorSelectionCriteria::RESIDENT_KEY_REQUIREMENT_REQUIRED,
			),
			excludeCredentials: $this->credentials->getDescriptorsByUserId($userId),
		);
		try {
			return $this->serializer->serialize($options, 'json');
		} catch (ExceptionInterface $e) {
			throw new PasskeyRegistrationOptionsSerializationException(previous: $e);
		}
	}


	/**
	 * @throws PasskeyChallengeInvalidException
	 * @throws PasskeyCredentialAlreadyRegisteredException
	 * @throws PasskeyRegistrationAttestationResponseValidatorException
	 * @throws PasskeyRegistrationCredentialDeserializationException
	 * @throws PasskeyRegistrationCredentialIdTooShortException
	 * @throws PasskeyRegistrationCredentialRecordSerializationException
	 * @throws PasskeyRegistrationCrossOriginRegistrationException
	 * @throws PasskeyRegistrationInvalidTypeException
	 */
	#[Override]
	public function verifyRegistration(string $json, string $name, int $userId, string $userHandle): void
	{
		$challenge = $this->getRemoveChallenge(self::SESSION_REG_CHALLENGE);
		try {
			$credential = $this->serializer->deserialize($json, PublicKeyCredential::class, 'json');
		} catch (ExceptionInterface $e) {
			throw new PasskeyRegistrationCredentialDeserializationException(previous: $e);
		}

		$attestationResponse = $credential->response;
		if (!$attestationResponse instanceof AuthenticatorAttestationResponse) {
			throw new PasskeyRegistrationInvalidTypeException();
		}

		if ($attestationResponse->clientDataJSON->crossOrigin === true) {
			throw new PasskeyRegistrationCrossOriginRegistrationException();
		}

		$credentialId = $credential->rawId;
		if ($this->isCredentialIdTooShort($credentialId)) {
			throw new PasskeyRegistrationCredentialIdTooShortException();
		}

		$options = PublicKeyCredentialCreationOptions::create(
			PublicKeyCredentialRpEntity::create($this->rpName, $this->rpId),
			PublicKeyCredentialUserEntity::create('', $userHandle, ''),
			$challenge,
			$this->getPubKeyCredParams(),
			excludeCredentials: $this->credentials->getDescriptorsByUserId($userId),
		);

		try {
			$credentialRecord = $this->attestationResponseValidator->check($attestationResponse, $options, $this->rpId);
		} catch (Throwable $e) {
			throw new PasskeyRegistrationAttestationResponseValidatorException(previous: $e);
		}
		try {
			$credentialRecordJson = $this->serializer->serialize($credentialRecord, 'json');
		} catch (ExceptionInterface $e) {
			throw new PasskeyRegistrationCredentialRecordSerializationException(previous: $e);
		}

		$this->credentials->saveCredential($credentialRecord->publicKeyCredentialId, $credentialRecordJson, $name, $userId);
	}


	/**
	 * @throws PasskeyAuthenticationOptionsSerializationException
	 * @phpstan-impure
	 */
	#[Override]
	public function generateAuthenticationOptions(): string
	{
		$options = PublicKeyCredentialRequestOptions::create(
			$this->createChallenge(self::SESSION_AUTH_CHALLENGE),
			$this->rpId,
			userVerification: PublicKeyCredentialRequestOptions::USER_VERIFICATION_REQUIREMENT_REQUIRED,
		);
		try {
			return $this->serializer->serialize($options, 'json');
		} catch (ExceptionInterface $e) {
			throw new PasskeyAuthenticationOptionsSerializationException(previous: $e);
		}
	}


	/**
	 * @throws PasskeyAuthenticationAssertionResponseValidatorException
	 * @throws PasskeyAuthenticationCredentialDeserializationException
	 * @throws PasskeyAuthenticationCredentialIdTooShortException
	 * @throws PasskeyAuthenticationCredentialRecordDeserializationException
	 * @throws PasskeyAuthenticationCredentialRecordSerializationException
	 * @throws PasskeyAuthenticationCrossOriginAuthenticationException
	 * @throws PasskeyAuthenticationInvalidTypeException
	 * @throws PasskeyAuthenticationUnknownCredentialException
	 * @throws PasskeyAuthenticationUserNotFoundException
	 * @throws PasskeyChallengeInvalidException
	 */
	#[Override]
	public function verifyAuthentication(string $json): PasskeyAuthenticationResult
	{
		$challenge = $this->getRemoveChallenge(self::SESSION_AUTH_CHALLENGE);
		try {
			$credential = $this->serializer->deserialize($json, PublicKeyCredential::class, 'json');
		} catch (ExceptionInterface $e) {
			throw new PasskeyAuthenticationCredentialDeserializationException(previous: $e);
		}

		$assertionResponse = $credential->response;
		if (!$assertionResponse instanceof AuthenticatorAssertionResponse) {
			throw new PasskeyAuthenticationInvalidTypeException();
		}

		if ($assertionResponse->clientDataJSON->crossOrigin === true) {
			throw new PasskeyAuthenticationCrossOriginAuthenticationException();
		}

		$credentialId = $credential->rawId;
		if ($this->isCredentialIdTooShort($credentialId)) {
			throw new PasskeyAuthenticationCredentialIdTooShortException();
		}

		$credentialRecordJson = $this->credentials->findCredentialRecordJsonByCredentialId($credentialId);
		if ($credentialRecordJson === null) {
			throw new PasskeyAuthenticationUnknownCredentialException($credentialId);
		}

		try {
			$credentialRecord = $this->serializer->deserialize($credentialRecordJson, CredentialRecord::class, 'json');
		} catch (ExceptionInterface $e) {
			throw new PasskeyAuthenticationCredentialRecordDeserializationException(previous: $e);
		}

		$options = PublicKeyCredentialRequestOptions::create(
			$challenge,
			$this->rpId,
			userVerification: PublicKeyCredentialRequestOptions::USER_VERIFICATION_REQUIREMENT_REQUIRED,
		);

		try {
			$credentialRecord = $this->assertionResponseValidator->check($credentialRecord, $assertionResponse, $options, $this->rpId, null);
		} catch (AuthenticatorResponseVerificationException $e) {
			throw new PasskeyAuthenticationAssertionResponseValidatorException(previous: $e);
		}

		$user = $this->credentials->getUserByCredentialId($credentialId);
		if ($user === null) {
			throw new PasskeyAuthenticationUserNotFoundException();
		}

		try {
			$updatedCredentialRecordJson = $this->serializer->serialize($credentialRecord, 'json');
		} catch (ExceptionInterface $e) {
			throw new PasskeyAuthenticationCredentialRecordSerializationException(previous: $e);
		}
		$this->credentials->updateCredentialAfterAuthentication($credentialId, $updatedCredentialRecordJson);

		return new PasskeyAuthenticationResult($user->id, $user->username);
	}


	private function getSession(): SessionSection
	{
		return $this->session->getSection('passkey');
	}


	/**
	 * @phpstan-impure
	 */
	private function createChallenge(string $sessionKey): string
	{
		$challenge = random_bytes(32);
		$this->getSession()->set($sessionKey, $challenge);
		return $challenge;
	}


	/**
	 * @throws PasskeyChallengeInvalidException
	 * @phpstan-impure
	 */
	private function getRemoveChallenge(string $sessionKey): string
	{
		$challenge = $this->getSession()->get($sessionKey);
		$this->getSession()->remove($sessionKey);
		if (!is_string($challenge) || $challenge === '') {
			throw new PasskeyChallengeInvalidException();
		}
		return $challenge;
	}


	private function isCredentialIdTooShort(string $credentialId): bool
	{
		return strlen($credentialId) < 16;
	}


	/**
	 * @return list<PublicKeyCredentialParameters>
	 */
	private function getPubKeyCredParams(): array
	{
		return [
			PublicKeyCredentialParameters::createPk(Algorithms::COSE_ALGORITHM_ES256), // ECDSA with SHA-256, supported by most authenticators
			PublicKeyCredentialParameters::createPk(Algorithms::COSE_ALGORITHM_RS256), // RSASSA-PKCS1-v1_5 with SHA-256, for Windows Hello
		];
	}

}
