<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Parser\FieldProcessors;

use DateMalformedStringException;
use DateTimeImmutable;
use Override;
use Spaze\SecurityTxt\Exceptions\SecurityTxtError;
use Spaze\SecurityTxt\Exceptions\SecurityTxtWarning;
use Spaze\SecurityTxt\Fields\SecurityTxtExpiresFactory;
use Spaze\SecurityTxt\SecurityTxt;

final readonly class ExpiresSetFieldValue implements FieldProcessor
{

	public function __construct(
		private SecurityTxtExpiresFactory $expiresFactory,
	) {
	}


	/**
	 * @throws SecurityTxtError
	 * @throws SecurityTxtWarning
	 */
	#[Override]
	public function process(string $value, SecurityTxt $securityTxt): void
	{
		try {
			$securityTxt->setExpires($this->expiresFactory->create(new DateTimeImmutable($value)));
		} catch (DateMalformedStringException) {
			// can't set the Expires value
		}
	}

}
