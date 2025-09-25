<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Parser\FieldProcessors;

use Closure;
use Override;
use Spaze\SecurityTxt\Exceptions\SecurityTxtWarning;
use Spaze\SecurityTxt\SecurityTxt;
use Spaze\SecurityTxt\Violations\SecurityTxtExpiresSoon;

final readonly class ExpiresCheckFieldValueExpiresSoon implements FieldProcessor
{

	/**
	 * @param Closure(): (int|null) $expiresWarningThresholdCallback
	 */
	public function __construct(
		private Closure $expiresWarningThresholdCallback,
	) {
	}


	/**
	 * @throws SecurityTxtWarning
	 */
	#[Override]
	public function process(string $value, SecurityTxt $securityTxt): void
	{
		$expires = $securityTxt->getExpires();
		if ($expires === null || $expires->isExpired()) {
			return;
		}
		$expiresWarningThreshold = ($this->expiresWarningThresholdCallback)();
		if ($expiresWarningThreshold !== null && $expires->inDays() < $expiresWarningThreshold) {
			throw new SecurityTxtWarning(new SecurityTxtExpiresSoon($expires->inDays()));
		}
	}

}
