<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Http\SecurityHeaders\IntegrityPolicy;

enum IntegrityPolicyBlockedDestination: string
{

	case Script = 'script';

}
