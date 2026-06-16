#!/usr/bin/env php
<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Bin;

use MichalSpacekCz\Application\Bootstrap;
use MichalSpacekCz\User\WebAuthn\Exceptions\PasskeyRegistrationDisabledException;
use MichalSpacekCz\User\WebAuthn\Registration\Exceptions\PasskeyRegistrationUrlArgsException;
use MichalSpacekCz\User\WebAuthn\Registration\Exceptions\PasskeyRegistrationUrlUserNotFoundException;
use MichalSpacekCz\User\WebAuthn\Registration\PasskeyAddUrl;

require __DIR__ . '/../vendor/autoload.php';

$addUrl = Bootstrap::bootCli(PasskeyAddUrl::class)->getByType(PasskeyAddUrl::class);
try {
	echo "To add a new passkey, open this link while signed in:\n" . $addUrl->generate() . "\n";
	exit(0);
} catch (PasskeyRegistrationUrlArgsException $e) {
	fprintf(STDERR, "Usage: passkey-add.php <username>\nGenerates a one-time link to add a new passkey for <username>, open it while signed in as that user.\n%s\n", $e->getMessage());
	exit(1);
} catch (PasskeyRegistrationDisabledException) {
	fprintf(STDERR, "Error: passkey registration is disabled. To enable it, set authentication.passkeys.registrationEnabled: true in local.neon\n");
	exit(1);
} catch (PasskeyRegistrationUrlUserNotFoundException $e) {
	fprintf(STDERR, "Error: user '%s' not found\n", $e->getUsername());
	exit(1);
}
