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
 * One value on its way into a stored result, the inverse of what `SecurityTxtJson::create*FromJsonValues()` reads back. An object this library holds is spelled the way it
 * prints, a scalar goes as it is, and anything else is refused rather than stored as the `{}` `json_encode()` would write it as.
 *
 * @internal A caller wanting a value as a string wants `SecurityTxtPrintableValue`, which encodes what this deliberately does not
 */
final readonly class SecurityTxtJsonValue
{

	/**
	 * @param mixed $value Whatever a constructor was called with, which for a violation is `func_get_args()`, so `toValue()` is what narrows it
	 */
	public function __construct(private mixed $value)
	{
	}


	/**
	 * @return scalar|null|array<array-key, mixed> What `json_encode()` can write without losing it
	 * @throws LogicException
	 */
	public function toValue(): string|int|float|bool|array|null
	{
		if ($this->value instanceof Url || $this->value instanceof SecurityTxtHost) {
			return new SecurityTxtPrintableValue($this->value)->render();
		}
		if ($this->value instanceof SecurityTxtRedirects) {
			return $this->value->toStrings();
		}
		if ($this->value instanceof BackedEnum) {
			return $this->value->value;
		}
		if (is_array($this->value)) {
			return array_map(fn(mixed $value): string|int|float|bool|array|null => new self($value)->toValue(), $this->value);
		}
		if (!is_scalar($this->value) && $this->value !== null) {
			throw new LogicException(sprintf('A stored result cannot carry a %s', get_debug_type($this->value)));
		}
		return $this->value;
	}

}
