<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Parser\SplitProviders;

use Override;

final class SecurityTxtPregSplitProvider implements SecurityTxtSplitProvider
{

	#[Override]
	public function split(string $pattern, string $subject, bool $noEmpty = false): array|false
	{
		return @preg_split($pattern, $subject, flags: $noEmpty ? PREG_SPLIT_NO_EMPTY : 0); // Intentionally silenced
	}

}
