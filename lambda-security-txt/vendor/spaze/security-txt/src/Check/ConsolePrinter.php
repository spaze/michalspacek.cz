<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Check;

use Spaze\SecurityTxt\SecurityTxtHost;
use Spaze\SecurityTxt\SecurityTxtPrintableValue;
use Uri\WhatWg\Url;

/**
 * Prints messages built from a format and values.
 *
 * The format is written in this codebase and is the only part that can ask for colors, and the only part that may be anything other than printable ASCII. The values come
 * from the checked host, a `Location` header or a field value read from its `security.txt` for example, and are encoded, never read as markup. Colors are resolved in the
 * format before the values are put in it, so a value can neither color itself nor reach the terminal as an escape sequence.
 *
 * @internal Should be used only in the check host class
 */
final class ConsolePrinter
{

	private const array COLORS = [
		'<b>' => "\033[1m",
		'<red>' => "\033[1;31m",
		'<green>' => "\033[1;32m",
		'<gray>' => "\033[1;90m",
		'</b>' => "\033[0m",
		'</red>' => "\033[0m",
		'</green>' => "\033[0m",
		'</gray>' => "\033[0m",
	];

	private bool $colors = false;


	public function setColors(bool $colors): void
	{
		$this->colors = $colors;
	}


	/**
	 * @param literal-string $format
	 */
	public function ok(string $format, string|Url|SecurityTxtHost ...$values): void
	{
		$this->print('<green>[OK]</green>', $format, $values);
	}


	/**
	 * @param literal-string $format
	 */
	public function info(string $format, string|Url|SecurityTxtHost ...$values): void
	{
		$this->print('<gray>[Info]</gray>', $format, $values);
	}


	/**
	 * @param literal-string $format
	 */
	public function error(string $format, string|Url|SecurityTxtHost ...$values): void
	{
		$this->print('<red>[Error]</red>', $format, $values);
	}


	/**
	 * @param literal-string $format
	 */
	public function warning(string $format, string|Url|SecurityTxtHost ...$values): void
	{
		$this->print('<b>[Warning]</b>', $format, $values);
	}


	/**
	 * Prints text the application itself wrote, a usage message for one, the way it arrived: not read as a format, so a `%` in it stays a `%`, and not encoded, so a word in
	 * it keeps its accents. Only what a checked host sends has to be treated as neither.
	 */
	public function infoText(string $text): void
	{
		$this->printText($this->addColors('<gray>[Info]</gray>'), $text);
	}


	/**
	 * @param literal-string $format
	 * @param array<array-key, string|Url|SecurityTxtHost> $values Used in the order they came in, `vsprintf()` does not read the keys
	 */
	private function print(string $level, string $format, array $values): void
	{
		$this->printText($this->addColors($level), vsprintf($this->addColors($format), array_map(fn(string|Url|SecurityTxtHost $value): string => new SecurityTxtPrintableValue($value)->render(), $values)));
	}


	private function printText(string $level, string $text): void
	{
		$text = str_replace("\n", "\n{$level} ", $text);
		echo "{$level} {$text}\n";
	}


	private function addColors(string $format): string
	{
		$replacements = $this->colors ? array_values(self::COLORS) : array_fill(0, count(self::COLORS), '');
		return str_replace(array_keys(self::COLORS), $replacements, $format);
	}

}
