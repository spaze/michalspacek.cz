#!/usr/bin/env php
<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Bin;

use MichalSpacekCz\Application\Bootstrap;
use MichalSpacekCz\User\WebAuthn\Exceptions\PasskeyRegistrationDisabledException;
use MichalSpacekCz\User\WebAuthn\Registration\Exceptions\PasskeyRegistrationUrlArgsException;
use MichalSpacekCz\User\WebAuthn\Registration\Exceptions\PasskeyRegistrationUrlUserNotFoundException;
use MichalSpacekCz\User\WebAuthn\Registration\PasskeyResetUrl;

require __DIR__ . '/../vendor/autoload.php';

$resetUrl = Bootstrap::bootCli(PasskeyResetUrl::class)->getByType(PasskeyResetUrl::class);
try {
	echo 'To register a passkey and revoke the user\'s other passkeys, tokens, and sessions, go to ' . $resetUrl->generate() . "\n";
	exit(0);
} catch (PasskeyRegistrationUrlArgsException $e) {
	fprintf(STDERR, "Usage: passkey-reset.php <username>\nGenerates a recovery link that registers a new passkey and revokes the user's other passkeys, tokens, and sessions. To add a passkey without revoking, use passkey-add.php.\n%s\n", $e->getMessage());
	exit(1);
} catch (PasskeyRegistrationDisabledException) {
	fprintf(STDERR, "Error: passkey reset is disabled. To enable it, set authentication.passkeys.registrationEnabled: true in local.neon\n");
	exit(1);
} catch (PasskeyRegistrationUrlUserNotFoundException $e) {
	fprintf(STDERR, "Error: user '%s' not found\n", $e->getUsername());
	exit(1);
}
