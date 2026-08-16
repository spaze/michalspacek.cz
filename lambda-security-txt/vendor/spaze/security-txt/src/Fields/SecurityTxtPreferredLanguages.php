<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Fields;

use JsonSerializable;
use Override;

final readonly class SecurityTxtPreferredLanguages implements SecurityTxtFieldValue, JsonSerializable
{

	/**
	 * @internal
	 */
	public const string SEPARATOR = ',';


	/**
	 * @param list<string> $languages
	 */
	public function __construct(
		private array $languages,
	) {
	}


	#[Override]
	public function getField(): SecurityTxtField
	{
		return SecurityTxtField::PreferredLanguages;
	}


	#[Override]
	public function getValue(): string
	{
		return implode(self::SEPARATOR, $this->languages);
	}


	/**
	 * @return list<string>
	 */
	public function getLanguages(): array
	{
		return $this->languages;
	}


	/**
	 * @return array<string, mixed>
	 */
	#[Override]
	public function jsonSerialize(): array
	{
		return [
			'languages' => $this->getLanguages(),
		];
	}

}
