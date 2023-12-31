<?php
declare(strict_types = 1);

namespace MichalSpacekCz\DateTime;

use DateTimeInterface;
use IntlDateFormatter;
use RuntimeException;

readonly class DateTimeFormatter
{

	private const DATE_DAY = 'day';
	private const DATE_MONTH = 'month';

	private const NO_INTERVAL = 1;
	private const INTERVAL = 2;
	private const INTERVAL_BOUNDARY = 3;
	private const INTERVAL_BOUNDARIES = 4;

	private const INTERVAL_FORMAT_START = 1;
	private const INTERVAL_FORMAT_SEPARATOR = 2;
	private const INTERVAL_FORMAT_END = 3;

	private const LOCAL_DATE_FORMAT = [
		'en_US' => [
			self::DATE_DAY => [
				self::NO_INTERVAL => 'MMMM d, y',
				self::INTERVAL => [
					self::INTERVAL_FORMAT_START => 'MMMM d',
					self::INTERVAL_FORMAT_SEPARATOR => '–',
					self::INTERVAL_FORMAT_END => 'd, y',
				],
				self::INTERVAL_BOUNDARY => [
					self::INTERVAL_FORMAT_START => 'MMMM d',
					self::INTERVAL_FORMAT_SEPARATOR => ' – ',
					self::INTERVAL_FORMAT_END => 'MMMM d, y',
				],
				self::INTERVAL_BOUNDARIES => [
					self::INTERVAL_FORMAT_START => 'MMMM d, y',
					self::INTERVAL_FORMAT_SEPARATOR => ' – ',
					self::INTERVAL_FORMAT_END => 'MMMM d, y',
				],
			],
			self::DATE_MONTH => [
				self::NO_INTERVAL => 'MMMM y',
				self::INTERVAL => [
					self::INTERVAL_FORMAT_START => 'MMMM',
					self::INTERVAL_FORMAT_SEPARATOR => '–',
					self::INTERVAL_FORMAT_END => 'MMMM y',
				],
				self::INTERVAL_BOUNDARY => [
					self::INTERVAL_FORMAT_START => 'MMMM y',
					self::INTERVAL_FORMAT_SEPARATOR => ' – ',
					self::INTERVAL_FORMAT_END => 'MMMM y',
				],
			],
		],
		// Date formats from http://prirucka.ujc.cas.cz/?id=810
		'cs_CZ' => [
			self::DATE_DAY => [
				self::NO_INTERVAL => 'd. MMMM y',
				self::INTERVAL => [
					self::INTERVAL_FORMAT_START => 'd.',
					self::INTERVAL_FORMAT_SEPARATOR => '–',
					self::INTERVAL_FORMAT_END => 'd. MMMM y',
				],
				self::INTERVAL_BOUNDARY => [
					self::INTERVAL_FORMAT_START => 'd. MMMM',
					self::INTERVAL_FORMAT_SEPARATOR => ' – ',
					self::INTERVAL_FORMAT_END => 'd. MMMM y',
				],
				self::INTERVAL_BOUNDARIES => [
					self::INTERVAL_FORMAT_START => 'd. MMMM y',
					self::INTERVAL_FORMAT_SEPARATOR => ' – ',
					self::INTERVAL_FORMAT_END => 'd. MMMM y',
				],
			],
			self::DATE_MONTH => [
				self::NO_INTERVAL => 'LLLL y',
				self::INTERVAL => [
					self::INTERVAL_FORMAT_START => 'LLLL',
					self::INTERVAL_FORMAT_SEPARATOR => '–',
					self::INTERVAL_FORMAT_END => 'LLLL y',
				],
				self::INTERVAL_BOUNDARY => [
					self::INTERVAL_FORMAT_START => 'LLLL y',
					self::INTERVAL_FORMAT_SEPARATOR => ' – ',
					self::INTERVAL_FORMAT_END => 'LLLL y',
				],
			],
		],
	];

	private const COMPARISON_FORMAT = [
		self::DATE_DAY => [
			self::NO_INTERVAL => 'Ymd',
			self::INTERVAL => 'Ym',
			self::INTERVAL_BOUNDARY => 'Y',
		],
		self::DATE_MONTH => [
			self::NO_INTERVAL => 'Ym',
			self::INTERVAL => 'Y',
			self::INTERVAL_BOUNDARY => null,
		],
	];


	public function __construct(
		private string $defaultLocale,
	) {
	}


	private function localeDate(DateTimeInterface $start, ?DateTimeInterface $end, string $type, ?string $locale): string
	{
		if ($locale === null) {
			$locale = $this->defaultLocale;
		}

		$formatter = new IntlDateFormatter($locale, IntlDateFormatter::NONE, IntlDateFormatter::NONE);
		if ($end === null || $this->sameDates($start, $end, $type, self::NO_INTERVAL)) {
			$formatter->setPattern(self::LOCAL_DATE_FORMAT[$locale][$type][self::NO_INTERVAL]);
			$result = $formatter->format($start);
		} else {
			if ($this->sameDates($start, $end, $type, self::INTERVAL)) {
				$format = self::LOCAL_DATE_FORMAT[$locale][$type][self::INTERVAL];
			} elseif (
				isset(self::LOCAL_DATE_FORMAT[$locale][$type][self::INTERVAL_BOUNDARIES])
				&& !$this->sameDates($start, $end, $type, self::INTERVAL_BOUNDARY)
			) {
				$format = self::LOCAL_DATE_FORMAT[$locale][$type][self::INTERVAL_BOUNDARIES];
			} else {
				$format = self::LOCAL_DATE_FORMAT[$locale][$type][self::INTERVAL_BOUNDARY];
			}

			$formatter->setPattern($format[self::INTERVAL_FORMAT_START]);
			$result = $formatter->format($start);

			$result .= $format[self::INTERVAL_FORMAT_SEPARATOR];

			$formatter->setPattern($format[self::INTERVAL_FORMAT_END]);
			$result .= $formatter->format($end);
		}
		if (!$result) {
			throw new RuntimeException("Format '{$type}' using {$locale} has failed");
		}
		return $result;
	}


	public function localeDay(DateTimeInterface $date, ?string $locale = null): string
	{
		return $this->localeDate($date, null, self::DATE_DAY, $locale);
	}


	public function localeMonth(DateTimeInterface $date, ?string $locale = null): string
	{
		return $this->localeDate($date, null, self::DATE_MONTH, $locale);
	}


	public function localeIntervalDay(DateTimeInterface $start, DateTimeInterface $end, ?string $locale = null): string
	{
		return $this->localeDate($start, $end, self::DATE_DAY, $locale);
	}


	public function localeIntervalMonth(DateTimeInterface $start, DateTimeInterface $end, ?string $locale = null): string
	{
		return $this->localeDate($start, $end, self::DATE_MONTH, $locale);
	}


	private function sameDates(DateTimeInterface $start, DateTimeInterface $end, string $type, int $level): bool
	{
		return isset(self::COMPARISON_FORMAT[$type][$level]) && ($start->format(self::COMPARISON_FORMAT[$type][$level]) === $end->format(self::COMPARISON_FORMAT[$type][$level]));
	}

}
