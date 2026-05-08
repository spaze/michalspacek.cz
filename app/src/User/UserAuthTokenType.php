<?php
declare(strict_types = 1);

namespace MichalSpacekCz\User;

enum UserAuthTokenType: int
{

	case PermanentLogin = 1;
	// ReturningUser = 2; not used anymore
	case PasskeyReset = 3;

}
