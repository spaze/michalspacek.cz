<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Violations;

use JsonSerializable;
use Override;
use Spaze\SecurityTxt\Json\SecurityTxtJsonValue;
use Spaze\SecurityTxt\SecurityTxtHost;
use Spaze\SecurityTxt\SecurityTxtPrintableValue;
use Uri\WhatWg\Url;
use ValueError;

abstract class SecurityTxtSpecViolation implements JsonSerializable
{

	private readonly string $message;

	private readonly string $howToFix;

	/** @var list<string|Url|SecurityTxtHost> */
	private readonly array $messageValues;

	/** @var list<string|Url|SecurityTxtHost> */
	private readonly array $howToFixValues;


	/**
	 * @param list<mixed> $constructorParams
	 * @param literal-string $messageFormat Never build this from a field value or anything else read from the file, only the values are encoded when the message is printed. The analysers check this where the violation is constructed in code, they cannot check `SecurityTxtJson`, which replays whatever the serialized params hold
	 * @param array<array-key, string|Url|SecurityTxtHost> $messageValues A value the library knows to be a URL or a host is passed as one, so it reads as itself instead of percent encoded. Stored as a list, see the constructor
	 * @param literal-string $howToFixFormat Never build this from a field value or anything else read from the file, only the values are encoded when the message is printed
	 * @param array<array-key, string|Url|SecurityTxtHost> $howToFixValues Stored as a list, see the constructor
	 * @param list<string> $seeAlsoSections
	 * @throws ValueError
	 */
	public function __construct(
		private readonly array $constructorParams,
		private readonly string $messageFormat,
		array $messageValues,
		private readonly ?string $since,
		private readonly string|Url|null $correctValue,
		private readonly string $howToFixFormat,
		array $howToFixValues,
		private readonly ?string $specSection,
		private readonly array $seeAlsoSections = [],
		private readonly ?string $specUrl = null,
	) {
		// Code always passes a list, but `SecurityTxtJson` replays whatever the serialized params hold, and a string key there would be read as a named argument by anything
		// spreading the values into a call, so the getters can promise a list only if it is made one here
		$this->messageValues = array_values($messageValues);
		$this->howToFixValues = array_values($howToFixValues);
		// Rendered here and not in the getter so that a format and values that disagree fail where `SecurityTxtJson` guards a replay of serialized params
		// `vsprintf()` refuses too few values but ignores surplus ones, which would silently shift every value after them once two formats are composed for printing
		assert(substr_count($this->messageFormat, '%s') === count($this->messageValues));
		assert(substr_count($this->howToFixFormat, '%s') === count($this->howToFixValues));
		$this->message = vsprintf($this->messageFormat, array_map(fn(string|Url|SecurityTxtHost $value): string => new SecurityTxtPrintableValue($value)->render(), $this->messageValues));
		$this->howToFix = vsprintf($this->howToFixFormat, array_map(fn(string|Url|SecurityTxtHost $value): string => new SecurityTxtPrintableValue($value)->render(), $this->howToFixValues));
	}


	public function getMessage(): string
	{
		return $this->message;
	}


	/**
	 * A value the violation knows to be a URL, handed over as one so that it reads as itself rather than percent encoded. Stays a string when it will not parse, which is what a
	 * violation about a malformed URI carries, and keeps whatever a host wrote when parsing would rewrite it, because the rewrite is often the very thing being reported.
	 *
	 * @return ($value is null ? null : string|Url)
	 */
	final protected static function asUrl(?string $value): string|Url|null
	{
		if ($value === null) {
			return null;
		}
		$url = Url::parse($value);
		// Either canonical spelling counts: a URL this library wrote reaches a violation as A-labels, one read out of a checked file as whatever it said. What still fails
		// both is a value that parses as something other than itself, `HTTP://` or a missing slash, which is often the finding and must not be printed normalised
		return $url !== null && ($url->toUnicodeString() === $value || $url->toAsciiString() === $value) ? $url : $value;
	}


	/**
	 * `$piece` repeated once per item, separated by `$separator`, empty when there are none.
	 *
	 * @param literal-string $piece
	 * @param literal-string $separator
	 * @return literal-string
	 */
	protected function getRepeatedFormat(string $piece, int $count, string $separator = ', '): string
	{
		$format = '';
		for ($i = 0; $i < $count; $i++) {
			$format .= $format === '' ? $piece : $separator . $piece;
		}
		return $format;
	}


	/**
	 * @return literal-string
	 */
	public function getMessageFormat(): string
	{
		return $this->messageFormat;
	}


	/**
	 * @return list<string|Url|SecurityTxtHost>
	 */
	public function getMessageValues(): array
	{
		return $this->messageValues;
	}


	public function getSince(): ?string
	{
		return $this->since;
	}


	public function getCorrectValue(): string|Url|null
	{
		return $this->correctValue;
	}


	public function getHowToFix(): string
	{
		return $this->howToFix;
	}


	/**
	 * @return literal-string
	 */
	public function getHowToFixFormat(): string
	{
		return $this->howToFixFormat;
	}


	/**
	 * @return list<string|Url|SecurityTxtHost>
	 */
	public function getHowToFixValues(): array
	{
		return $this->howToFixValues;
	}


	public function getSpecSection(): ?string
	{
		return $this->specSection;
	}


	/**
	 * @return list<string>
	 */
	public function getSeeAlsoSections(): array
	{
		return $this->seeAlsoSections;
	}


	public function getSpecUrl(): ?string
	{
		return $this->specUrl;
	}


	/**
	 * @return array<string, mixed>
	 */
	#[Override]
	public function jsonSerialize(): array
	{
		return [
			'class' => $this::class,
			'params' => array_map(fn(mixed $param): string|int|float|bool|array|null => new SecurityTxtJsonValue($param)->toValue(), $this->constructorParams),
		];
	}

}
