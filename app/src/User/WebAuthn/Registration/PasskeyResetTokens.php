<?php
declare(strict_types = 1);

namespace MichalSpacekCz\User\WebAuthn\Registration;

use Exception;
use MichalSpacekCz\DateTime\DateTimeFactory;
use MichalSpacekCz\User\AuthTokens\UserAuthToken;
use MichalSpacekCz\User\AuthTokens\UserAuthTokenLifetime;
use MichalSpacekCz\User\AuthTokens\UserAuthTokens;
use MichalSpacekCz\User\AuthTokens\UserAuthTokenType;
use MichalSpacekCz\User\WebAuthn\Registration\Exceptions\PasskeyRegistrationDisabledException;
use Override;

final readonly class PasskeyResetTokens implements UserAuthTokenLifetime, PasskeyRegistrationTokens
{

	public function __construct(
		private UserAuthTokens $tokens,
		private DateTimeFactory $dateTimeFactory,
		private bool $registrationEnabled,
		private string $ttl,
	) {
	}


	#[Override]
	public function getTokenType(): UserAuthTokenType
	{
		return UserAuthTokenType::AdminPasskeyReset;
	}


	#[Override]
	public function getTtl(): string
	{
		return $this->ttl;
	}


	#[Override]
	public function deleteExpired(): int
	{
		return $this->tokens->deleteExpiredByType($this->getTokenType(), $this->dateTimeFactory->create('-' . $this->getTtl()));
	}


	#[Override]
	public function isEnabled(): bool
	{
		return $this->registrationEnabled;
	}


	/**
	 * @throws PasskeyRegistrationDisabledException
	 * @throws Exception
	 */
	#[Override]
	public function create(int $userId): string
	{
		if (!$this->registrationEnabled) {
			throw new PasskeyRegistrationDisabledException();
		}
		return $this->tokens->replaceForUser($userId, $this->getTokenType());
	}


	/**
	 * @throws PasskeyRegistrationDisabledException
	 */
	#[Override]
	public function verify(string $value): ?UserAuthToken
	{
		if (!$this->registrationEnabled) {
			throw new PasskeyRegistrationDisabledException();
		}
		return $this->tokens->verify($value, $this->dateTimeFactory->create('-' . $this->getTtl()), $this->getTokenType());
	}


	#[Override]
	public function deleteById(int $tokenId, int $userId): int
	{
		return $this->tokens->deleteById($tokenId, $this->getTokenType(), $userId);
	}

}
