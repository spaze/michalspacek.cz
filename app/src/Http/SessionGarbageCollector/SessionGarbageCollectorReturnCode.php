<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Http\SessionGarbageCollector;

enum SessionGarbageCollectorReturnCode: int
{

	case Ok = 0;
	case GcFailure = 1;
	case Exception = 2;

}
