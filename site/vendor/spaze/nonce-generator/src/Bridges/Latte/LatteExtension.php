<?php
declare(strict_types = 1);

namespace Spaze\NonceGenerator\Bridges\Latte;

use Latte\Extension;
use Spaze\NonceGenerator\Nonce;

class LatteExtension extends Extension
{

	public function __construct(
		private readonly Nonce $nonce,
	) {
	}


	/**
	 * @return array{uiNonce:string}
	 */
	public function getProviders(): array
	{
		return [
			'uiNonce' => $this->nonce->getValue(),
		];
	}

}
