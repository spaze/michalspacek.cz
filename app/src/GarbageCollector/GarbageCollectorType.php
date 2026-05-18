<?php
declare(strict_types = 1);

namespace MichalSpacekCz\GarbageCollector;

enum GarbageCollectorType: int
{

	case Sessions = 1;
	case AuthTokens = 2;

}
