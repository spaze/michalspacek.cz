#!/usr/bin/env php
<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Check;

use Spaze\SecurityTxt\Fetcher\HttpClients\SecurityTxtFetcherFopenClient;
use Spaze\SecurityTxt\Fetcher\SecurityTxtFetcher;
use Spaze\SecurityTxt\Fetcher\SecurityTxtFetchResultFactory;
use Spaze\SecurityTxt\Fields\SecurityTxtExpiresFactory;
use Spaze\SecurityTxt\Json\SecurityTxtJson;
use Spaze\SecurityTxt\Parser\SecurityTxtParser;
use Spaze\SecurityTxt\Parser\SecurityTxtUrlParser;
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
$signature = new SecurityTxtSignature();
$fopenClient = new SecurityTxtFetcherFopenClient('Mozilla/5.0 (compatible; spaze/security-txt; +https://github.com/spaze/security-txt)');
$fetcher = new SecurityTxtFetcher($fopenClient);
$expiresFactory = new SecurityTxtExpiresFactory();
$parser = new SecurityTxtParser($validator, $signature, $fetcher, $expiresFactory);
$urlParser = new SecurityTxtUrlParser();
$consolePrinter = new ConsolePrinter();
$json = new SecurityTxtJson();
$fetchResultFactory = new SecurityTxtFetchResultFactory($json);
$checkHostResultFactory = new SecurityTxtCheckHostResultFactory($json, $fetchResultFactory);
$checkHost = new SecurityTxtCheckHost($parser, $urlParser, $fetcher, $checkHostResultFactory);
$checkHostCli = new SecurityTxtCheckHostCli($consolePrinter, $checkHost);

/** @var list<string> $args */
$args = is_array($_SERVER['argv']) ? $_SERVER['argv'] : [];
$checkHostCli->check(
	$args[0],
	$args[1] ?? null,
	isset($args[2]) && $args[2] !== '' ? (int)$args[2] : null,
	in_array('--colors', $args, true),
	in_array('--strict', $args, true),
	in_array('--no-ipv6', $args, true),
);
