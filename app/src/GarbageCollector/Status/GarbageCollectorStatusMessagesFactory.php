<?php
declare(strict_types = 1);

namespace MichalSpacekCz\GarbageCollector\Status;

interface GarbageCollectorStatusMessagesFactory
{

	public function create(): GarbageCollectorStatusMessages;

}
