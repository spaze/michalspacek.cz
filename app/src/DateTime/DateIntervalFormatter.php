<?php
declare(strict_types = 1);

namespace MichalSpacekCz\DateTime;

use Contributte\Translation\Translator;
use DateInterval;

final readonly class DateIntervalFormatter
{

	public function __construct(
		private Translator $translator,
	) {
	}


	public function toMinutesSecondsAgo(DateInterval $interval): string
	{
		$minutes = ($interval->days !== false ? $interval->days * 24 * 60 : 0) + $interval->h * 60 + $interval->i;
		if ($minutes < 1) {
			return $this->translator->translate('messages.timeIntervalAgo.seconds', $interval->s);
		}
		return $this->translator->translate('messages.timeIntervalAgo.minutes', $minutes);
	}

}
