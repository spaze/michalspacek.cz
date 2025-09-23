<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Http\SessionGarbageCollector;

interface SessionGarbageCollectorStatusMessageFactory
{

	public function create(): SessionGarbageCollectorStatusMessage;

}
