<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Utils;

final class Base64
{

	private const array LAST_TWO_STANDARD = ['+', '/'];
	private const array LAST_TWO_URL_VARIANT = ['-', '_'];


	public static function urlEncode(string $string): string
	{
		$string = base64_encode($string);
		$string = rtrim($string, '=');
		return str_replace(self::LAST_TWO_STANDARD, self::LAST_TWO_URL_VARIANT, $string);
	}


	public static function urlDecode(string $encoded): string
	{
		$encoded = str_replace(self::LAST_TWO_URL_VARIANT, self::LAST_TWO_STANDARD, $encoded);
		return base64_decode($encoded);
	}

}
