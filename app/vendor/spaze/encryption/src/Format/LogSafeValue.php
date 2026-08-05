<?php
declare(strict_types = 1);

namespace Spaze\Encryption\Format;

/**
 * @internal Values repeated in exception messages can come from stored data or from a key pasted into the wrong config slot, so they can be anything: keep them short and printable before they hit a log.
 */
class LogSafeValue
{

	private const MAX_LENGTH = 20;


	public static function from(string $value): string
	{
		$shortened = strlen($value) > self::MAX_LENGTH ? substr($value, 0, self::MAX_LENGTH) . '...' : $value;
		return preg_replace('/[^!-~]/', '?', $shortened) ?? $shortened;
	}

}
