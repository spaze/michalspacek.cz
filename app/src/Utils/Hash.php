<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Utils;

class Hash
{

	public static function nonCryptographic(string $data, bool $binary = false): string
	{
		return hash('xxh128', $data, $binary);
	}

}
