<?php
declare(strict_types = 1);

namespace Spaze\NonceGenerator;

/**
 * Nonce generator interface.
 *
 * @author Michal Špaček
 */
interface GeneratorInterface
{
	public function getNonce();
}
