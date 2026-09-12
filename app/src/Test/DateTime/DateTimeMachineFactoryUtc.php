<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Test\DateTime;

use MichalSpacekCz\DateTime\DateTimeFactoryUtc;

/**
 * The time machine for code that asked for UTC, so a test can decide "now" there too.
 */
final class DateTimeMachineFactoryUtc extends DateTimeFactoryUtc
{

	use DateTimeMachine;

}
