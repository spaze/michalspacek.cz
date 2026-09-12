<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Test\DateTime;

use MichalSpacekCz\DateTime\DateTimeFactory;

/**
 * This (date) time machine allows you to time-travel.
 * Supposed to be used in tests to create DateTimeImmutable objects with known dates.
 */
final class DateTimeMachineFactory extends DateTimeFactory
{

	use DateTimeMachine;

}
