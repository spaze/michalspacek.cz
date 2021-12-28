<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Http;

class HttpHeader
{

	public static function normalizeValue(string $value): string
	{
		return str_replace('\\', '/', $value);
	}

}
