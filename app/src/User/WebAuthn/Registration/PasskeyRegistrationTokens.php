<?php
declare(strict_types = 1);

namespace MichalSpacekCz\User\WebAuthn\Registration;

use MichalSpacekCz\User\AuthTokens\UserAuthToken;
use MichalSpacekCz\User\WebAuthn\Registration\Exceptions\PasskeyRegistrationDisabledException;

/**
 * One-time tokens that authorize registering a passkey out of band.
 *
 * Each token type is its own implementation. The shared registration code (the
 * from-token flow and the link generator) depends only on this interface, so it
 * works the same for every token type; only delivery differs, e.g. the reset link
 * is anonymous account recovery while the add link is opened behind login.
 */
interface PasskeyRegistrationTokens
{

	/**
	 * @return bool Whether registering a passkey with this token type is currently allowed
	 */
	public function isEnabled(): bool;


	/**
	 * @return string The selector:token value to put in the registration link
	 * @throws PasskeyRegistrationDisabledException
	 */
	public function create(int $userId): string;


	/**
	 * @return UserAuthToken|null The matching token, or null when the value is invalid, expired, or belongs to a different token type
	 * @throws PasskeyRegistrationDisabledException
	 */
	public function verify(string $value): ?UserAuthToken;


	public function deleteById(int $tokenId): void;

}
