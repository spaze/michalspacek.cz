<?php
declare(strict_types = 1);

namespace MichalSpacekCz\User\SecurityActivity;

/**
 * The kinds of security-relevant account events recorded in the security_events table.
 *
 * The string value is stored verbatim in the database and the log is never garbage-collected, so old
 * rows keep their value forever. Add new cases freely and never repurpose an existing value for a
 * different meaning. Renaming a value is only safe together with a migration that updates the stored
 * rows in the same deploy; read with tryFrom() so any un-migrated value from an older row degrades to
 * its raw string instead of throwing.
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
	case SignInPermanent = 'signin.permanent';
	case ReauthIntervalSuccess = 'reauth.interval.success';
	case ReauthIntervalFailure = 'reauth.interval.failure';
	case ReauthInlineSuccess = 'reauth.inline.success';
	case ReauthInlineFailure = 'reauth.inline.failure';
	case ResetRevokeFailed = 'reset.revoke.failed';
	case NotificationEmailChanged = 'notification.email.changed';
	case PageViewed = 'page.viewed';


	/**
	 * @return string Translation key for the human-readable label, e.g. messages.account.securityLog.event.passkeyAddFinished
	 */
	public function labelKey(): string
	{
		return 'messages.account.securityLog.event.' . lcfirst($this->name);
	}

}
