<?php
declare(strict_types = 1);

namespace Spaze\NonceGenerator;

/**
 * Nonce Generator service.
 *
 * @author Michal Špaček
 */
class Generator implements GeneratorInterface
{

	/** @var string */
	protected $nonce;


	/**
	 * Get nonce.
	 *
	 * @return string
	 */
	public function getNonce(): string
	{
		if ($this->nonce === null) {
			$this->nonce = base64_encode(random_bytes(16));
		}
		return $this->nonce;
	}

}
