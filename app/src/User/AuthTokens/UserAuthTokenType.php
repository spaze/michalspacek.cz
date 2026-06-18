<?php
declare(strict_types = 1);

namespace MichalSpacekCz\User\AuthTokens;

enum UserAuthTokenType: int
{

	case PermanentLogin = 1;
	// ReturningUser = 2; not used anymore
	case AdminPasskeyReset = 3;
	case AdminPasskeyAdd = 4;

}
