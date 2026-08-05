<?php
declare(strict_types = 1);

namespace Spaze\Encryption\Exceptions;

use Exception;
use Spaze\Encryption\Format\AsymmetricKeyRole;
use Spaze\Encryption\Format\LogSafeValue;
use Throwable;

class InvalidKeyRoleException extends Exception
{

	public function __construct(string $id, AsymmetricKeyRole $expectedRole, AsymmetricKeyRole $actualRole, ?Throwable $previous = null)
	{
		parent::__construct("Key '" . LogSafeValue::from($id) . "' is tagged as a {$actualRole->value} key but is used as a {$expectedRole->value} key", previous: $previous);
	}

}
