<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Violations;

/**
 * What the Canonical fields list instead of the URI the file was fetched from, decided by the validator, which has them parsed, and carried by the violation, so one
 * rebuilt from JSON says the same without them being compared again
 */
enum SecurityTxtCanonicalUriMismatchReason: string
{

	/**
	 * Nothing more to say, another host, a location a redirect took elsewhere, or a violation stored before it carried the reason
	 */
	case Unspecified = 'unspecified';

	/**
	 * The file was fetched from the top-level path, and the fields list the one under the `/.well-known/` path
	 */
	case WellKnownListed = 'well-known-listed';

	/**
	 * The file was fetched from under the `/.well-known/` path, and the fields list the one at the top-level path
	 */
	case TopLevelListed = 'top-level-listed';

	/**
	 * The file was fetched from under the `/.well-known/` path, and a field lists another URI on the same host, a typo in the path say
	 */
	case SameHost = 'same-host';

	/**
	 * The file was fetched from under the `/.well-known/` path, and a field lists something that isn't a URI
	 */
	case NotUri = 'not-uri';

}
