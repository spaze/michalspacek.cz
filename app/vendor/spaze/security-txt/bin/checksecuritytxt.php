#!/usr/bin/env php
<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Check;

use Spaze\SecurityTxt\Fetcher\HttpClients\SecurityTxtFetcherFopenClient;
use Spaze\SecurityTxt\Fetcher\SecurityTxtFetcher;
use Spaze\SecurityTxt\Fields\SecurityTxtExpiresFactory;
use Spaze\SecurityTxt\Parser\SecurityTxtParser;
use Spaze\SecurityTxt\Parser\SecurityTxtSplitLines;
use Spaze\SecurityTxt\Parser\SecurityTxtUrlParser;
use Spaze\SecurityTxt\Signature\Providers\SecurityTxtSignatureGnuPgProvider;
use Spaze\SecurityTxt\Signature\SecurityTxtSignature;
use Spaze\SecurityTxt\Validator\SecurityTxtValidator;

$autoloadFiles = [
	__DIR__ . '/../vendor/autoload.php',
	__DIR__ . '/../../../autoload.php',
];

$autoloadLoaded = false;
foreach ($autoloadFiles as $autoloadFile) {
	if (is_file($autoloadFile)) {
		require_once $autoloadFile;
		$autoloadLoaded = true;
		break;
	}
}

if (!$autoloadLoaded) {
	fwrite(STDERR, "Install packages using Composer.\n");
	exit(254);
}

$validator = new SecurityTxtValidator();
$gnuPgProvider = new SecurityTxtSignatureGnuPgProvider();
$signature = new SecurityTxtSignature($gnuPgProvider);
$fopenClient = new SecurityTxtFetcherFopenClient('Mozilla/5.0 (compatible; spaze/security-txt; +https://github.com/spaze/security-txt)');
$urlParser = new SecurityTxtUrlParser();
$expiresFactory = new SecurityTxtExpiresFactory();
$splitLines = new SecurityTxtSplitLines();
$parser = new SecurityTxtParser($validator, $signature, $expiresFactory, $splitLines);
$fetcher = new SecurityTxtFetcher($fopenClient, $urlParser, $splitLines);
$consolePrinter = new ConsolePrinter();
$checkHostResultFactory = new SecurityTxtCheckHostResultFactory();
$checkHost = new SecurityTxtCheckHost($parser, $urlParser, $fetcher, $checkHostResultFactory);
$checkHostCli = new SecurityTxtCheckHostCli($consolePrinter, $checkHost);

/** @var list<string> $args */
$args = is_array($_SERVER['argv']) ? $_SERVER['argv'] : [];
$checkHostCli->check(
	$args[1] ?? null,
	isset($args[2]) && $args[2] !== '' ? (int)$args[2] : null,
	in_array('--colors', $args, true),
	in_array('--strict', $args, true),
	in_array('--require-top-level-location', $args, true),
	in_array('--no-ipv6', $args, true),
	'Usage: ' . basename(__FILE__) . " <URL or hostname> [days] [--colors] [--strict] [--require-top-level-location] [--no-ipv6]\n"
		. "If the file expires in less than <days>, the script will print a warning.\n"
		. "When --require-top-level-location is specified, the /security.txt location must also exist or be redirected, otherwise a warning will be issued.\n"
		. "The check will return 1 instead of 0 if any of the following conditions are true: the file has expired, has errors, or has warnings when using --strict.",
);
