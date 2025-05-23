<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Check;

use Spaze\SecurityTxt\Parser\SecurityTxtParseHostResult;

final readonly class SecurityTxtCheckHostResultFactory
{

	public function create(string $host, SecurityTxtParseHostResult $parseResult): SecurityTxtCheckHostResult
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
			$parseResult->isExpiresSoon(),
			$parseResult->getSecurityTxt()->getExpires()?->isExpired(),
			$parseResult->getSecurityTxt()->getExpires()?->inDays(),
			$parseResult->isValid(),
			$parseResult->isStrictMode(),
			$parseResult->getExpiresWarningThreshold(),
		);
	}

}
