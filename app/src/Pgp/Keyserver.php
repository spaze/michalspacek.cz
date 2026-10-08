<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Pgp;

use Composer\Pcre\Preg;

final readonly class Keyserver
{

	private const string LOOKUP_URL = 'https://keyserver.ubuntu.com/pks/lookup?search=0x%s&op=index';


	/**
	 * Accepts a fingerprint, a long key id or a short one, with or without the `0x` a keyserver wants in front of it.
	 */
	public function getLookupUrl(string $keyIdOrFingerprint): string
	{
		return sprintf(self::LOOKUP_URL, Preg::replace('~^0x~i', '', $keyIdOrFingerprint));
	}

}
