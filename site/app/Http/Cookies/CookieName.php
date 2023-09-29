<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Http\Cookies;

enum CookieName: string
{

	case PermanentLogin = '__Secure-permanent';
	case ReturningUser = '__Secure-beenhere';
	case Theme = 'future';

}
