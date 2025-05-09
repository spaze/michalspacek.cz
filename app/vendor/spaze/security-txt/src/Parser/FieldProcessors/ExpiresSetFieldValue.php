<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Parser\FieldProcessors;

use DateTimeImmutable;
use Exception;
use Override;
use Spaze\SecurityTxt\Exceptions\SecurityTxtError;
use Spaze\SecurityTxt\Exceptions\SecurityTxtWarning;
use Spaze\SecurityTxt\Fields\SecurityTxtExpires;
use Spaze\SecurityTxt\Fields\SecurityTxtExpiresFactory;
use Spaze\SecurityTxt\SecurityTxt;
use Spaze\SecurityTxt\Violations\SecurityTxtExpiresOldFormat;
use Spaze\SecurityTxt\Violations\SecurityTxtExpiresWrongFormat;

final readonly class ExpiresSetFieldValue implements FieldProcessor
{

	public function __construct(
		private SecurityTxtExpiresFactory $expiresFactory,
	) {
	}


	/**
	 * @throws SecurityTxtError
	 * @throws SecurityTxtWarning
	 * @throws Exception
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
					throw new SecurityTxtError(new SecurityTxtExpiresOldFormat($expiresValue));
				} else {
					try {
						$expiresValue = new DateTimeImmutable($value);
					} catch (Exception) {
						$expiresValue = null;
					}
					throw new SecurityTxtError(new SecurityTxtExpiresWrongFormat($expiresValue));
				}
			}
		}
		$expires = $this->expiresFactory->create(new DateTimeImmutable($value));
		$securityTxt->setExpires($expires);
	}

}
