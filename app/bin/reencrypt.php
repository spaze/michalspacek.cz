#!/usr/bin/env php
<?php
declare(strict_types = 1);

/*
 * Re-encrypts stored values that the encryption library reports as needing it: encrypted with a key that is no
 * longer the active one, or written in a storage format the library has since moved on from.
 *
 * - Run manually after a key rotation, or after an encryption library upgrade that changes the stored format
 * - Use --dry-run to see what would be re-encrypted without writing anything, also useful to verify a previous real run left nothing behind
 *
 * Sessions are not swept: the session handler re-encrypts a session whenever its data changes, and rows expire
 * anyway, so they migrate on their own. That means a rotated session key has to stay configured until the last
 * pre-rotation session has expired, because the handler decrypts without catching anything and an old session
 * whose key is gone is an error on every request that visitor makes.
 */

namespace MichalSpacekCz\Bin;

use MichalSpacekCz\Application\Bootstrap;
use MichalSpacekCz\Encryption\ReEncryption;

require __DIR__ . '/../vendor/autoload.php';

$reEncryption = Bootstrap::bootCli(ReEncryption::class)->getByType(ReEncryption::class);
exit($reEncryption->run());
