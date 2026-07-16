<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Http\Cookies;

enum CookieName: string
{

	// Cookies we set
	case PermanentLogin = '__Secure-permanent';
	case Theme = 'future';

	// Cookies set by vendor code
	case NetteSameSiteCheck = '_nss'; // Same as in Nette\Http\Helpers::StrictCookieName, marked as @internal - the name is tested in CookieDescriptionsTest

}
