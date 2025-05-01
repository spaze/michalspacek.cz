<?php
declare(strict_types = 1);

namespace MichalSpacekCz\DateTime;

/**
 * Extra class to make it obvious that it's using UTC.
 * In other words, if you look at the dependencies of some other class, and there's this one there,
 * you'll immediately know that the timezone will be UTC in that other class.
 */
final class DateTimeFactoryUtc extends DateTimeFactory
{

	public function __construct(
		DateTimeZoneFactory $dateTimeZoneFactory,
	) {
		parent::__construct($dateTimeZoneFactory, 'UTC');
	}

}
