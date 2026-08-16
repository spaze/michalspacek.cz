<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Parser\SplitProviders;

interface SecurityTxtSplitProvider
{

	/**
	 * @param non-empty-string $pattern
	 * @return list<string>|false
	 */
	public function split(string $pattern, string $subject, bool $noEmpty = false): array|false;

}
