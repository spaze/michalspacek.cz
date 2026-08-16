<?php
declare(strict_types = 1);

use Composer\InstalledVersions;
use Spaze\SecurityTxt\Check\SecurityTxtCheckHost;
use Spaze\SecurityTxt\Check\SecurityTxtCheckHostResult;
use Spaze\SecurityTxt\Check\SecurityTxtCheckHostResultFactory;
use Spaze\SecurityTxt\Fetcher\DnsLookup\SecurityTxtPhpDnsProvider;
use Spaze\SecurityTxt\Fetcher\Exceptions\SecurityTxtFetcherException;
use Spaze\SecurityTxt\Fetcher\HttpClients\SecurityTxtFetcherCurlClient;
use Spaze\SecurityTxt\Fetcher\SecurityTxtFetcher;
use Spaze\SecurityTxt\Fetcher\SecurityTxtFetchResult;
use Spaze\SecurityTxt\Fetcher\SecurityTxtIpAddressValidator;
use Spaze\SecurityTxt\Fields\SecurityTxtExpiresFactory;
use Spaze\SecurityTxt\Parser\SecurityTxtParser;
use Spaze\SecurityTxt\Parser\SecurityTxtSplitLines;
use Spaze\SecurityTxt\Parser\SecurityTxtUrlParser;
use Spaze\SecurityTxt\Parser\SplitProviders\SecurityTxtPregSplitProvider;
use Spaze\SecurityTxt\Signature\Providers\SecurityTxtSignatureGnuPgProvider;
use Spaze\SecurityTxt\Signature\SecurityTxtSignature;
use Spaze\SecurityTxt\Validator\SecurityTxtValidator;

require __DIR__ . '/../vendor/autoload.php';

/**
 * @param array{host:string, userAgent:string} $event
 * @return array{status:string, fetchResult?:SecurityTxtFetchResult, checkHostResult?:SecurityTxtCheckHostResult, error?:SecurityTxtFetcherException}
 */
return function (array $event): array {
	if (!isset($event['host'])) {
		throw new RuntimeException('Missing host parameter');
	}
	if (!is_string($event['host'])) {
		throw new RuntimeException('The host parameter is not a string');
	}
	if (!isset($event['userAgent'])) {
		throw new RuntimeException('Missing userAgent parameter');
	}
	if (!is_string($event['userAgent'])) {
		throw new RuntimeException('The userAgent parameter is not a string');
	}
	$curlClient = new SecurityTxtFetcherCurlClient($event['userAgent']);
	$urlParser = new SecurityTxtUrlParser();
	$splitProvider = new SecurityTxtPregSplitProvider();
	$splitLines = new SecurityTxtSplitLines($splitProvider);
	$dnsProvider = new SecurityTxtPhpDnsProvider();
	$ipAddressValidator = new SecurityTxtIpAddressValidator();
	$fetcher = new SecurityTxtFetcher($curlClient, $urlParser, $splitLines, $dnsProvider, $ipAddressValidator);
	$requireTopLevelLocation = isset($event['requireTopLevelLocation']) && $event['requireTopLevelLocation'];
	$noIpv6 = isset($event['noIpv6']) && $event['noIpv6'];
	$fetchResultOnly = !isset($event['checkHost']) || !$event['checkHost'];

	$libVersion = [
		'libVersion' => InstalledVersions::getVersion('spaze/security-txt'),
		'libReference' => InstalledVersions::getReference('spaze/security-txt'),
	];
	try {
		$url = $urlParser->getUrl($event['host']);
		if ($fetchResultOnly) {
			$checkHostResult = null;
			$fetchResult = $fetcher->fetch($url, $requireTopLevelLocation, $noIpv6);
		} else {
			$validator = new SecurityTxtValidator();
			$gnuPgProvider = new SecurityTxtSignatureGnuPgProvider();
			$signature = new SecurityTxtSignature($gnuPgProvider);
			$expiresFactory = new SecurityTxtExpiresFactory();
			$parser = new SecurityTxtParser($validator, $signature, $expiresFactory, $splitLines, $splitProvider);
			$checkHostResultFactory = new SecurityTxtCheckHostResultFactory();
			$checkHost = new SecurityTxtCheckHost($parser, $fetcher, $checkHostResultFactory);
			$checkHostResult = $checkHost->check($url, null, false, $requireTopLevelLocation, $noIpv6);
			$fetchResult = $checkHostResult->getFetchResult();
		}
		return $libVersion + [
			'status' => 'OK',
			'fetchResult' => $fetchResult,
			'checkHostResult' => $checkHostResult,
		];
	} catch (SecurityTxtFetcherException $e) {
		return $libVersion + [
			'status' => 'Error',
			'error' => $e,
		];
	}
};
