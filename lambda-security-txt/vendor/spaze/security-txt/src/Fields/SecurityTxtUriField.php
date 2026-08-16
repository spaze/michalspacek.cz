<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Fields;

use JsonSerializable;
use Override;

abstract class SecurityTxtUriField implements SecurityTxtFieldValue, JsonSerializable
{

	final public function __construct(
		private readonly string $uri,
	) {
	}


	public function getUri(): string
	{
		return $this->uri;
	}


	#[Override]
	public function getValue(): string
	{
		return $this->uri;
	}


	/**
	 * @return array<string, mixed>
	 */
	#[Override]
	public function jsonSerialize(): array
	{
		return [
			'uri' => $this->getUri(),
		];
	}

}
