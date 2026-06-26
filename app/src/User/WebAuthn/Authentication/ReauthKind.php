<?php
declare(strict_types = 1);

namespace MichalSpacekCz\User\WebAuthn\Authentication;

/**
 * Interval gates *viewing* a sensitive page and only runs once the freshness window has lapsed; inline
 * gates *submitting* a specific change and runs on every submit. Either one refreshes the window on
 * success; they're logged as distinct events only so the log shows which gate fired.
 */
enum ReauthKind
{

	case Interval;
	case Inline;

}
