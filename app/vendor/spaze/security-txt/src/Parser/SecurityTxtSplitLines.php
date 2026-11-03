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
	 * After each LF, and after a CR with no LF after it, which RFC 9116 doesn't count as a line ending, but which would join two lines into one otherwise
	 *
	 * @return list<string>
	 */
	public function splitLines(string $contents): array
	{
		$lines = $this->splitProvider->split("/(?<=\n)|(?<=\r)(?!\n)/", $contents, true);
		if ($lines === false) {
			throw new LogicException('This should not happen');
		}
		return $lines;
	}

}
