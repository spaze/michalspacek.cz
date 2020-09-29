<?php
declare(strict_types = 1);

namespace Spaze\SubresourceIntegrity\Exceptions;

/**
 * UnsupportedHashAlgorithmException.
 *
 * @author Michal Špaček
 */
class UnsupportedHashAlgorithmException extends \RuntimeException
{

	public function __construct(\Throwable $previous = null)
	{
		parent::__construct('Unsupported hashing algorithm, choose one of sha256, sha384, sha512', 0, $previous);
	}

}
