<?php
declare(strict_types = 1);

namespace MichalSpacekCz\GarbageCollector;

enum GarbageCollectorReturnCode: int
{

	case Ok = 0;
	case Failure = 1;

}
