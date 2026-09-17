<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Json;

/**
 * One entry of a stored result, made by `SecurityTxtJsonValueFactory`.
 *
 * @internal
 */
final readonly class SecurityTxtJsonValue
{

	/**
	 * @param string|int|float|bool|array<array-key, mixed>|null $value
	 */
	public function __construct(
		private string $key,
		private string|int|float|bool|array|null $value,
	) {
	}


	public function getKey(): string
	{
		return $this->key;
	}


	/**
	 * @return string|int|float|bool|array<array-key, mixed>|null
	 */
	public function getValue(): string|int|float|bool|array|null
	{
		return $this->value;
	}

}
