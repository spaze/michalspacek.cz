<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Json;

use BackedEnum;
use LogicException;
use Spaze\SecurityTxt\Fetcher\SecurityTxtRedirects;
use Spaze\SecurityTxt\SecurityTxtHost;
use Spaze\SecurityTxt\SecurityTxtPrintableValue;
use Uri\WhatWg\Url;

/**
 * A value on its way into a stored result, under its key when `json_encode()` can write every string in it, under the key suffixed `Base64` with every string Base64 when it
 * cannot, so the reader decides by the key alone. `SecurityTxtJson::createStoredEntry()` is the way back.
 */
final readonly class SecurityTxtJsonValueFactory
{

	/**
	 * @param mixed $value Whatever a constructor was called with, which for a violation is `func_get_args()`
	 * @throws LogicException
	 */
	public function create(string $key, mixed $value): SecurityTxtJsonValue
	{
		$plain = $this->toValue($value, false);
		return $this->canBeWritten($plain) ? new SecurityTxtJsonValue($key, $plain) : new SecurityTxtJsonValue("{$key}Base64", $this->toValue($value, true));
	}


	/**
	 * The one UTF-8 test, the parser names `SecurityTxtContentNotUtf8` by it and the wire decides by it what goes as Base64.
	 */
	public function isUtf8(string $value): bool
	{
		if (@preg_match('//u', $value) === false) { // Intentionally silenced
			$pregError = preg_last_error();
			if ($pregError !== PREG_BAD_UTF8_ERROR) {
				throw new LogicException('preg_match() failed with PCRE error code ' . $pregError);
			}
			return false;
		}
		return true;
	}


	/**
	 * An object this library holds is spelled the way it prints, a scalar goes as it is, anything else is refused rather than stored as the `{}` `json_encode()` would write.
	 *
	 * @return string|int|float|bool|array<array-key, mixed>|null
	 */
	private function toValue(mixed $value, bool $base64): string|int|float|bool|array|null
	{
		if ($value instanceof Url || $value instanceof SecurityTxtHost) {
			$value = new SecurityTxtPrintableValue($value)->render();
		} elseif ($value instanceof SecurityTxtRedirects) {
			$value = $value->toStrings();
		} elseif ($value instanceof BackedEnum) {
			$value = $value->value;
		}
		if (is_array($value)) {
			return array_map(fn(mixed $item): string|int|float|bool|array|null => $this->toValue($item, $base64), $value);
		}
		if (is_string($value)) {
			return $base64 ? base64_encode($value) : $value;
		}
		if (!is_scalar($value) && $value !== null) {
			throw new LogicException(sprintf('A stored result cannot carry a %s', get_debug_type($value)));
		}
		return $value;
	}


	/**
	 * A key is what a value is found by, not a value, so one JSON cannot write is refused rather than spelled another way.
	 */
	private function canBeWritten(mixed $value): bool
	{
		if (is_array($value)) {
			$writable = true;
			foreach ($value as $key => $item) {
				if (is_string($key) && !$this->isUtf8($key)) {
					throw new LogicException('A stored result cannot carry a key that is not UTF-8');
				}
				$writable = $this->canBeWritten($item) && $writable;
			}
			return $writable;
		}
		return !is_string($value) || $this->isUtf8($value);
	}

}
