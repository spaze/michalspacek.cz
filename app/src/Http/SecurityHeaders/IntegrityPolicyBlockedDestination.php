<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Http\SecurityHeaders;

enum IntegrityPolicyBlockedDestination: string
{

	case Script = 'script';

}
