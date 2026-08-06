<?php
declare(strict_types = 1);

namespace Spaze\Encryption\Format;

/**
 * @internal The role tag a key value can carry between the prefix and the key itself, not part of the public API
 */
enum AsymmetricKeyRole: string
{

	case Secret = 'secret';
	case Public = 'public';

}
