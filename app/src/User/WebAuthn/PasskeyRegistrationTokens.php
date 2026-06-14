<?php
declare(strict_types = 1);

namespace MichalSpacekCz\User\WebAuthn;

use MichalSpacekCz\User\AuthTokens\UserAuthToken;
use MichalSpacekCz\User\WebAuthn\Exceptions\PasskeyRegistrationDisabledException;

/**
 * One-time tokens that authorize registering a passkey out of band.
 *
 * Each token type is its own implementation. The code that checks the token and
 * registers the passkey depends only on this interface, so it works the same for
 * every token type. Creating tokens is not part of the interface because each
 * type creates and delivers its token differently.
 */
interface PasskeyRegistrationTokens
{

	/**
	 * @return UserAuthToken|null The matching token, or null when the value is invalid, expired, or belongs to a different token type
	 * @throws PasskeyRegistrationDisabledException
	 */
	public function verify(string $value): ?UserAuthToken;


	public function deleteById(int $tokenId): void;

}
