<?php
declare(strict_types = 1);

namespace MichalSpacekCz\User\SecurityActivity;

/**
 * The kinds of security-relevant account events recorded in the security_events table.
 *
 * The string value is a permanent serialization contract: it's stored verbatim in the database and the
 * log is never garbage-collected, so old rows keep their value forever. Add new cases freely, but never
 * rename or repurpose an existing value, and read with tryFrom() so an unknown value from an older row
 * degrades to its raw string instead of throwing.
 */
enum SecurityEventType: string
{

	case PasskeyAddInitiated = 'passkey.add.initiated';
	case PasskeyAddFinished = 'passkey.add.finished';
	case PasskeyResetInitiated = 'passkey.reset.initiated';
	case PasskeyResetFinished = 'passkey.reset.finished';
	case PasskeyRenamed = 'passkey.renamed';
	case PasskeyDeleted = 'passkey.deleted';
	case SignInSuccess = 'signin.success';
	case ReauthIntervalSuccess = 'reauth.interval.success';
	case ReauthIntervalFailure = 'reauth.interval.failure';
	case ReauthInlineSuccess = 'reauth.inline.success';
	case ReauthInlineFailure = 'reauth.inline.failure';
	case ResetRevokeFailed = 'reset.revoke.failed';
	case EmailChanged = 'email.changed';
	case PageViewed = 'page.viewed';


	/**
	 * @return string Translation key for the human-readable label, e.g. messages.account.securityLog.event.passkeyAddFinished
	 */
	public function labelKey(): string
	{
		return 'messages.account.securityLog.event.' . lcfirst($this->name);
	}

}
