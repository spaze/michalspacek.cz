<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Parser;

use LogicException;
use Spaze\SecurityTxt\Parser\SplitProviders\SecurityTxtSplitProvider;

final readonly class SecurityTxtSplitLines
{

	public function __construct(private SecurityTxtSplitProvider $splitProvider)
	{
	}


	/**
	 * @return list<string>
	 */
	public function splitLines(string $contents): array
	{
		$lines = $this->splitProvider->split("/(?<=\n)/", $contents, true);
		if ($lines === false) {
			throw new LogicException('This should not happen');
		}
		return $lines;
	}

}
