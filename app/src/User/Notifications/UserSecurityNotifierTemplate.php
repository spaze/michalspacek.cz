<?php
declare(strict_types = 1);

namespace MichalSpacekCz\User\Notifications;

enum UserSecurityNotifierTemplate: string
{

	case PasskeyAdded = 'passkeyAdded';
	case PasskeyReset = 'passkeyReset';
	case NotificationEmailChanged = 'notificationEmailChanged';
	case NotificationEmailChangedConfirmation = 'notificationEmailChangedConfirmation';

}
