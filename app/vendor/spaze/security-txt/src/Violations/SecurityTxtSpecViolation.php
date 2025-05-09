<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Violations;

use JsonSerializable;
use Override;

abstract class SecurityTxtSpecViolation implements JsonSerializable
{

	/**
	 * @param list<mixed> $constructorParams
	 * @param list<string> $messageValues
	 * @param list<string> $howToFixValues
	 * @param list<string> $seeAlsoSections
	 */
	public function __construct(
		private readonly array $constructorParams,
		private readonly string $messageFormat,
		private readonly array $messageValues,
		private readonly ?string $since,
		private readonly ?string $correctValue,
		private readonly string $howToFixFormat,
		private readonly array $howToFixValues,
		private readonly ?string $specSection,
		private readonly array $seeAlsoSections = [],
	) {
	}


	public function getMessage(): string
	{
		return vsprintf($this->messageFormat, $this->messageValues);
	}


	public function getMessageFormat(): string
	{
		return $this->messageFormat;
	}


	/**
	 * @return list<string>
	 */
	public function getMessageValues(): array
	{
		return $this->messageValues;
	}


	public function getSince(): ?string
	{
		return $this->since;
	}


	public function getCorrectValue(): ?string
	{
		return $this->correctValue;
	}


	public function getHowToFix(): string
	{
		return vsprintf($this->howToFixFormat, $this->howToFixValues);
	}


	public function getHowToFixFormat(): string
	{
		return $this->howToFixFormat;
	}


	/**
	 * @return list<string>
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


	/**
	 * @return array<string, mixed>
	 */
	#[Override]
	public function jsonSerialize(): array
	{
		return [
			'class' => $this::class,
			'params' => $this->constructorParams,
			'message' => $this->getMessage(),
			'messageFormat' => $this->getMessageFormat(),
			'messageValues' => $this->getMessageValues(),
			'since' => $this->getSince(),
			'correctValue' => $this->getCorrectValue(),
			'howToFix' => $this->getHowToFix(),
			'howToFixFormat' => $this->getHowToFixFormat(),
			'howToFixValues' => $this->getHowToFixValues(),
			'specSection' => $this->getSpecSection(),
			'seeAlsoSections' => $this->getSeeAlsoSections(),
		];
	}

}
