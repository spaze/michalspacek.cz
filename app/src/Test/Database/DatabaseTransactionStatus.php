<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Test\Database;

enum DatabaseTransactionStatus
{

	case None;
	case Started;
	case Committed;
	case RolledBack;

}
