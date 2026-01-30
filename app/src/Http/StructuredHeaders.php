<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Http;

use BackedEnum;
use MichalSpacekCz\Utils\Arrays;

final readonly class StructuredHeaders
{

	/**
	 * @param array<string, string|BackedEnum|list<string|BackedEnum>> $policy
	 */
	public function get(array $policy): string
	{
		$directives = [];
		foreach ($policy as $directive => $values) {
			if (is_array($values)) {
				$values = implode(' ', Arrays::filterEmpty(array_map($this->normalizeStructuredValue(...), $values)));
			} else {
				$values = $this->normalizeStructuredValue($values);
			}
			$directives[] = sprintf('%s=(%s)', $directive, $values);
		}
		return implode(', ', $directives);
	}


	private function normalizeStructuredValue(string|BackedEnum $value): string
	{
		if ($value instanceof BackedEnum) {
			return (string)$value->value;
		}
		$value = trim($value);
		return $value !== '' ? sprintf('"%s"', addcslashes($value, '\"')) : '';
	}

}
