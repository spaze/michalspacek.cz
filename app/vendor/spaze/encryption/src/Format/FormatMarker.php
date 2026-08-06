<?php
declare(strict_types = 1);

namespace Spaze\Encryption\Format;

/**
 * @internal The marker between the key id and the encrypted data. The values are stored in databases, so cases can only be added, never changed, hence the version numbers.
 */
enum FormatMarker: string
{

	case AuthenticatedPublicKeyV1 = 'AuthV1';
	case AuthenticatedPublicKeyWithAdV1 = 'AuthAdV1';
	case AnonymousPublicKeyV1 = 'AnonV1';
	case SymmetricKeyV1 = 'SymV1';
	case SymmetricKeyWithAdV1 = 'SymAdV1';

}
