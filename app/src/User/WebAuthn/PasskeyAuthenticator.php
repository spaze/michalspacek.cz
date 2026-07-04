<?php
declare(strict_types = 1);

namespace MichalSpacekCz\User\WebAuthn;

use Cose\Algorithms;
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
use MichalSpacekCz\User\WebAuthn\Authentication\PasskeyAuthenticationResult;
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
use Nette\Security\User;
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

	public function __construct(
		private AuthenticatorAttestationResponseValidator $attestationResponseValidator,
		private AuthenticatorAssertionResponseValidator $assertionResponseValidator,
		private SerializerInterface $serializer,
		private PasskeyStorage $passkeyStorage,
		private PasskeySessionSection $passkeySessionSection,
		private string $rpId,
		private string $rpName,
		User $user,
	) {
		$user->onLoggedOut[] = $this->passkeySessionSection->removeAll(...);
	}


	/**
	 * @throws PasskeyRegistrationOptionsSerializationException
	 * @phpstan-impure
	 */
	#[Override]
	public function generateRegistrationOptions(int $userId, string $username, bool $excludeExistingCredentials): string
	{
		$rp = PublicKeyCredentialRpEntity::create($this->rpName, $this->rpId);
		$userHandle = $this->passkeyStorage->getUserHandleByUserId($userId);
		$user = PublicKeyCredentialUserEntity::create($username, $userHandle, $username);
		$challenge = random_bytes(32);
		$this->passkeySessionSection->setRegChallenge($challenge);
		$options = PublicKeyCredentialCreationOptions::create(
			$rp,
			$user,
			$challenge,
			$this->getPubKeyCredParams(),
			AuthenticatorSelectionCriteria::create(
				userVerification: AuthenticatorSelectionCriteria::USER_VERIFICATION_REQUIREMENT_REQUIRED,
				residentKey: AuthenticatorSelectionCriteria::RESIDENT_KEY_REQUIREMENT_REQUIRED,
			),
			excludeCredentials: $excludeExistingCredentials ? $this->passkeyStorage->getDescriptorsByUserId($userId) : [],
		);
		try {
			return $this->serializer->serialize($options, 'json');
		} catch (ExceptionInterface $e) {
			throw new PasskeyRegistrationOptionsSerializationException(previous: $e);
		}
	}


	/**
	 * @return string The id of the newly registered passkey credential
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
	public function verifyRegistration(string $json, string $name, int $userId): string
	{
		$challenge = $this->getValidChallenge($this->passkeySessionSection->getRemoveRegChallenge());
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

		$userHandle = $this->passkeyStorage->getUserHandleByUserId($userId);
		$options = PublicKeyCredentialCreationOptions::create(
			PublicKeyCredentialRpEntity::create($this->rpName, $this->rpId),
			PublicKeyCredentialUserEntity::create('', $userHandle, ''),
			$challenge,
			$this->getPubKeyCredParams(),
			AuthenticatorSelectionCriteria::create(
				userVerification: AuthenticatorSelectionCriteria::USER_VERIFICATION_REQUIREMENT_REQUIRED,
			),
			excludeCredentials: $this->passkeyStorage->getDescriptorsByUserId($userId),
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

		$this->passkeyStorage->saveCredential($credentialRecord->publicKeyCredentialId, $credentialRecordJson, $name, $userId);
		return $credentialRecord->publicKeyCredentialId;
	}


	/**
	 * @throws PasskeyAuthenticationOptionsSerializationException
	 * @phpstan-impure
	 */
	#[Override]
	public function generateAuthenticationOptions(): string
	{
		$challenge = random_bytes(32);
		$this->passkeySessionSection->setAuthChallenge($challenge);
		$options = PublicKeyCredentialRequestOptions::create(
			$challenge,
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
	 * Check a passkey the user presents, without remembering it as the one they signed in with.
	 * Sign-in uses verifyAuthentication(), which does remember it; confirming identity later uses
	 * this, so confirming with a different passkey doesn't change which one counts as the sign-in.
	 *
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
	public function verifyAssertion(string $json): PasskeyAuthenticationResult
	{
		$challenge = $this->getValidChallenge($this->passkeySessionSection->getRemoveAuthChallenge());
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

		$credentialRecordJson = $this->passkeyStorage->findCredentialRecordJsonByCredentialId($credentialId);
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

		$user = $this->passkeyStorage->getUserByCredentialId($credentialId);
		if ($user === null) {
			throw new PasskeyAuthenticationUserNotFoundException();
		}

		try {
			$updatedCredentialRecordJson = $this->serializer->serialize($credentialRecord, 'json');
		} catch (ExceptionInterface $e) {
			throw new PasskeyAuthenticationCredentialRecordSerializationException(previous: $e);
		}
		$this->passkeyStorage->updateCredentialAfterAuthentication($credentialId, $updatedCredentialRecordJson);

		return new PasskeyAuthenticationResult($user->id, $user->username, $credentialId, $user->credentialName);
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
		$result = $this->verifyAssertion($json);
		$this->passkeySessionSection->setSignedInCredentialId($result->credentialId);
		return $result;
	}


	/**
	 * @throws PasskeyChallengeInvalidException
	 */
	private function getValidChallenge(?string $challenge): string
	{
		if ($challenge === null || $challenge === '') {
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
