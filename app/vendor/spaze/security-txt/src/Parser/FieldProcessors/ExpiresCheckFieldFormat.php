<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Parser\FieldProcessors;

use DateMalformedStringException;
use DateTimeImmutable;
use Override;
use Spaze\SecurityTxt\Exceptions\SecurityTxtError;
use Spaze\SecurityTxt\Fields\SecurityTxtExpires;
use Spaze\SecurityTxt\SecurityTxt;
use Spaze\SecurityTxt\Violations\SecurityTxtExpiresOldFormat;
use Spaze\SecurityTxt\Violations\SecurityTxtExpiresWrongFormat;

final readonly class ExpiresCheckFieldFormat implements FieldProcessor
{

	/**
	 * RFC 3339 section 5.6, the `T` and the `Z` in either case, any number of digits in a fraction of a second, and a 60th second, which it has for a leap second
	 */
	private const string DATE_TIME = '/^(\d{4})-(\d{2})-(\d{2})T(?:[01]\d|2[0-3]):[0-5]\d:(?:[0-5]\d|60)(?:\.\d++)?(?:Z|[+-](?:[01]\d|2[0-3]):[0-5]\d)$/iD';


	/**
	 * @throws SecurityTxtError
	 */
	#[Override]
	public function process(string $value, SecurityTxt $securityTxt): void
	{
		if ($this->isDateTime($value)) {
			return;
		}
		$correctValue = $this->getDateTime($value)?->format(SecurityTxtExpires::FORMAT);
		if ($correctValue !== null && DateTimeImmutable::createFromFormat(DATE_RFC2822, $value) !== false) {
			throw new SecurityTxtError(new SecurityTxtExpiresOldFormat($correctValue));
		}
		throw new SecurityTxtError(new SecurityTxtExpiresWrongFormat($correctValue));
	}


	/**
	 * The date and time the value means, as PHP reads it, but of a value that isn't RFC 3339 only when PHP didn't roll over a date or a time that doesn't exist,
	 * February 30 is not March 2, didn't move the date to the day of the week the value names, Mon, 29 Oct 2026 is not November 2, and what it read is RFC 3339,
	 * not a +24:00 offset
	 */
	public function getDateTime(string $value): ?DateTimeImmutable
	{
		// Which PHP reads as now
		if ($value === '') {
			return null;
		}
		try {
			$dateTime = new DateTimeImmutable($value);
		} catch (DateMalformedStringException) {
			return null;
		}
		// A leap second is rolled over too, into the next one
		if ($this->isDateTime($value)) {
			return $dateTime;
		}
		// A day of the week moves the date by six days at most, so to another day of the month, without one the day can be January 1 with seconds
		// or days to add, @1793314800 or 2026-W44-4
		$date = date_parse($value);
		$relative = $date['relative'] ?? null;
		if (is_array($relative) && isset($relative['weekday']) && $date['day'] !== false && (int)$dateTime->format('j') !== $date['day']) {
			return null;
		}
		return DateTimeImmutable::getLastErrors() === false && $this->isDateTime($dateTime->format(SecurityTxtExpires::FORMAT)) ? $dateTime : null;
	}


	private function isDateTime(string $value): bool
	{
		return preg_match(self::DATE_TIME, $value, $matches) === 1 && checkdate((int)$matches[2], (int)$matches[3], (int)$matches[1]);
	}

}
