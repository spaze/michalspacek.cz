<?php
declare(strict_types = 1);

namespace Spaze\NonceGenerator;

class Generator
{

	private ?string $nonce = null;


	/**
	 * @deprecated Use createNonce() instead
	 */
	public function getNonce(): string
	{
		if ($this->nonce === null) {
			$this->nonce = $this->createNonce()->getValue();
		}
		return $this->nonce;
	}


	public function createNonce(): Nonce
	{
		return new Nonce(\base64_encode(\random_bytes(18)));
	}

}
