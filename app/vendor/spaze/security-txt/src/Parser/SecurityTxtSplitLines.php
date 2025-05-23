<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Parser;

use LogicException;

final readonly class SecurityTxtSplitLines
{

	/**
	 * @return list<string>
	 */
	public function splitLines(string $contents): array
	{
		$lines = preg_split("/(?<=\n)/", $contents, flags: PREG_SPLIT_NO_EMPTY);
		if ($lines === false) {
			throw new LogicException('This should not happen');
		}
		return $lines;
	}

}
