<?php
declare(strict_types = 1);

namespace Spaze\NonceGenerator;

class Nonce
{

	public function __construct(
		private readonly string $value,
	) {
	}


	public function getValue(): string
	{
		return $this->value;
	}

}
