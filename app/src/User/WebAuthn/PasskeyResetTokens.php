<?php
declare(strict_types = 1);

namespace MichalSpacekCz\User\WebAuthn;

use DateTimeImmutable;
use Exception;
use MichalSpacekCz\User\AuthTokens\UserAuthToken;
use MichalSpacekCz\User\AuthTokens\UserAuthTokenLifetime;
use MichalSpacekCz\User\AuthTokens\UserAuthTokens;
use MichalSpacekCz\User\AuthTokens\UserAuthTokenType;
use MichalSpacekCz\User\WebAuthn\Exceptions\PasskeyResetDisabledException;
use Override;

final readonly class PasskeyResetTokens implements UserAuthTokenLifetime
{

	public function __construct(
		private UserAuthTokens $tokens,
		private bool $resetEnabled,
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
		return '5 minutes';
	}


	public function isEnabled(): bool
	{
		return $this->resetEnabled;
	}


	/**
	 * @throws PasskeyResetDisabledException
	 * @throws Exception
	 */
	public function create(int $userId): string
	{
		if (!$this->resetEnabled) {
			throw new PasskeyResetDisabledException();
		}
		return $this->tokens->replaceForUser($userId, $this->getTokenType());
	}


	/**
	 * @throws PasskeyResetDisabledException
	 */
	public function verify(string $value): ?UserAuthToken
	{
		if (!$this->resetEnabled) {
			throw new PasskeyResetDisabledException();
		}
		return $this->tokens->verify($value, new DateTimeImmutable('-' . $this->getTtl()), $this->getTokenType());
	}


	public function deleteById(int $tokenId): void
	{
		$this->tokens->deleteById($tokenId, $this->getTokenType());
	}

}
