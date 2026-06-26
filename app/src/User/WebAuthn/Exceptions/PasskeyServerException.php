<?php
declare(strict_types = 1);

namespace MichalSpacekCz\User\WebAuthn\Exceptions;

/**
 * Marks a passkey failure as worth an operator diagnostic rather than an ordinary user-side failure
 * (presenting a wrong, unknown, or expired passkey). That covers both our own fault (failed
 * (de)serialization of our options, our stored credential record, or the submitted credential) and a
 * genuine anomaly that isn't the user's doing, like a valid assertion resolving to a different account.
 */
interface PasskeyServerException
{
}
