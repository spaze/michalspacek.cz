<?php
declare(strict_types = 1);

namespace Spaze\NonceGenerator;

class Generator implements GeneratorInterface
{

	/** @var string */
	private $nonce;


	public function getNonce(): string
	{
		if ($this->nonce === null) {
			$this->nonce = \base64_encode(\random_bytes(16));
		}
		return $this->nonce;
	}

}
