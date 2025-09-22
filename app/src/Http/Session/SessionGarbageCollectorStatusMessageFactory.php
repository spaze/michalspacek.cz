<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Http\Session;

interface SessionGarbageCollectorStatusMessageFactory
{

	public function create(): SessionGarbageCollectorStatusMessage;

}
