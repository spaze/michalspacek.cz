#!/usr/bin/env php
<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Bin;

use MichalSpacekCz\Application\Bootstrap;
use MichalSpacekCz\User\WebAuthn\Exceptions\PasskeyRegistrationDisabledException;
use MichalSpacekCz\User\WebAuthn\Exceptions\PasskeyResetArgsException;
use MichalSpacekCz\User\WebAuthn\Exceptions\PasskeyResetUserNotFoundException;
use MichalSpacekCz\User\WebAuthn\PasskeyResetUrl;

require __DIR__ . '/../vendor/autoload.php';

$resetUrl = Bootstrap::bootCli(PasskeyResetUrl::class)->getByType(PasskeyResetUrl::class);
try {
	echo 'To register your passkey go to ' . $resetUrl->generate() . "\n";
	exit(0);
} catch (PasskeyResetArgsException $e) {
	fprintf(STDERR, "Usage: passkey-reset.php <username>\n%s\n", $e->getMessage());
	exit(1);
} catch (PasskeyRegistrationDisabledException) {
	fprintf(STDERR, "Error: passkey reset is disabled. To enable it, set authentication.passkeys.registrationEnabled: true in local.neon\n");
	exit(1);
} catch (PasskeyResetUserNotFoundException $e) {
	fprintf(STDERR, "Error: user '%s' not found\n", $e->getUsername());
	exit(1);
}
