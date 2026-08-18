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
		$minutes = $this->toMinutes($interval);
		if ($minutes < 1) {
			return $this->translator->translate('messages.timeIntervalAgo.seconds', $interval->s);
		}
		return $this->translator->translate('messages.timeIntervalAgo.minutes', $minutes);
	}


	public function toMinutesSecondsIn(DateInterval $interval): string
	{
		$minutes = $this->toMinutes($interval);
		if ($minutes < 1) {
			return $this->translator->translate('messages.timeIntervalIn.seconds', $interval->s);
		}
		return $this->translator->translate('messages.timeIntervalIn.minutes', $minutes);
	}


	private function toMinutes(DateInterval $interval): int
	{
		return ($interval->days !== false ? $interval->days * 24 * 60 : 0) + $interval->h * 60 + $interval->i;
	}

}
