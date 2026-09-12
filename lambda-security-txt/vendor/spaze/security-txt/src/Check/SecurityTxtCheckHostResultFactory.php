<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Check;

use Spaze\SecurityTxt\Parser\SecurityTxtParseHostResult;
use Spaze\SecurityTxt\SecurityTxtHost;

final readonly class SecurityTxtCheckHostResultFactory
{

	public function create(SecurityTxtHost $host, SecurityTxtParseHostResult $parseResult): SecurityTxtCheckHostResult
	{
		return new SecurityTxtCheckHostResult(
			$host,
			$parseResult->getFetchResult(),
			$parseResult->getFetchErrors(),
			$parseResult->getFetchWarnings(),
			$parseResult->getLineErrors(),
			$parseResult->getLineWarnings(),
			$parseResult->getFileErrors(),
			$parseResult->getFileWarnings(),
			$parseResult->getSecurityTxt(),
			$parseResult->getSecurityTxt()->getExpires()?->isExpired(),
			$parseResult->getSecurityTxt()->getExpires()?->inDays(),
			$parseResult->isValid(),
			$parseResult->isStrictMode(),
			$parseResult->getExpiresWarningThreshold(),
		);
	}

}
