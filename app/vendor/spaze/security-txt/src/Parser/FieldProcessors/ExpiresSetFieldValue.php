<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Parser\FieldProcessors;

use Override;
use Spaze\SecurityTxt\Exceptions\SecurityTxtError;
use Spaze\SecurityTxt\Exceptions\SecurityTxtWarning;
use Spaze\SecurityTxt\Fields\SecurityTxtExpiresFactory;
use Spaze\SecurityTxt\SecurityTxt;

final readonly class ExpiresSetFieldValue implements FieldProcessor
{

	public function __construct(
		private SecurityTxtExpiresFactory $expiresFactory,
		private ExpiresCheckFieldFormat $fieldFormat,
	) {
	}


	/**
	 * @throws SecurityTxtError
	 * @throws SecurityTxtWarning
	 */
	#[Override]
	public function process(string $value, SecurityTxt $securityTxt): void
	{
		$dateTime = $this->fieldFormat->getDateTime($value);
		if ($dateTime !== null) {
			$securityTxt->setExpires($this->expiresFactory->create($dateTime));
		}
	}

}
