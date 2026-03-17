<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Fields;

use JsonSerializable;
use Override;

final readonly class SecurityTxtBugBounty implements SecurityTxtFieldValue, JsonSerializable
{

	public function __construct(
		private bool $rewards,
	) {
	}


	#[Override]
	public function getField(): SecurityTxtField
	{
		return SecurityTxtField::BugBounty;
	}


	#[Override]
	public function getValue(): string
	{
		return $this->rewards ? 'True' : 'False';
	}


	public function rewards(): bool
	{
		return $this->rewards;
	}


	/**
	 * @return array<string, mixed>
	 */
	#[Override]
	public function jsonSerialize(): array
	{
		return [
			'rewards' => $this->rewards,
		];
	}

}
