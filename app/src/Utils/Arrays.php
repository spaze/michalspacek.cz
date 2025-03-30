<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Utils;

use Nette\Utils\Arrays as NetteArrays;

final readonly class Arrays
{

	/**
	 * @template K of int|string
	 * @template V of string|int|bool|null|array<array-key, mixed>
	 * @param array<K, V> $array
	 * @return array<K, V>
	 */
	public static function filterEmpty(array $array): array
	{
		$filter = function (string|int|bool|null|array $value): bool {
			if (is_string($value)) {
				return $value !== '';
			} elseif (is_int($value)) {
				return $value !== 0;
			} elseif (is_bool($value)) {
				return $value === true;
			} elseif (is_null($value)) {
				return false;
			}
			return $value !== [];
		};
		return NetteArrays::filter($array, $filter);
	}

}
