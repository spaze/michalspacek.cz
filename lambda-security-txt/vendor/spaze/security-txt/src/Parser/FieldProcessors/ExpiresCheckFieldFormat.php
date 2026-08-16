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
	 * @throws SecurityTxtError
	 */
	#[Override]
	public function process(string $value, SecurityTxt $securityTxt): void
	{
		$expiresValue = DateTimeImmutable::createFromFormat(SecurityTxtExpires::FORMAT, $value);
		if ($expiresValue === false) {
			$expiresValue = DateTimeImmutable::createFromFormat(DATE_RFC3339_EXTENDED, $value);
			if ($expiresValue === false) {
				$expiresValue = DateTimeImmutable::createFromFormat(DATE_RFC2822, $value);
				if ($expiresValue !== false) {
					throw new SecurityTxtError(new SecurityTxtExpiresOldFormat($expiresValue->format(SecurityTxtExpires::FORMAT)));
				} else {
					try {
						$expiresValue = new DateTimeImmutable($value);
					} catch (DateMalformedStringException) {
						$expiresValue = null;
					}
					throw new SecurityTxtError(new SecurityTxtExpiresWrongFormat($expiresValue?->format(SecurityTxtExpires::FORMAT)));
				}
			}
		}
	}

}
