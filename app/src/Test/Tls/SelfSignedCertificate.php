<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Test\Tls;

use OpenSSLAsymmetricKey;
use OpenSSLCertificate;
use OpenSSLCertificateSigningRequest;

/**
 * A certificate made on the spot for a test, valid from now, with the default extensions and no subjectAltName
 */
final class SelfSignedCertificate
{

	public static function issue(string $commonName, int $days): OpenSSLCertificate
	{
		$key = openssl_pkey_new(['private_key_type' => OPENSSL_KEYTYPE_EC, 'curve_name' => 'prime256v1']);
		assert($key instanceof OpenSSLAsymmetricKey);
		$keyForRequest = $key; // openssl_csr_new() takes the key by reference, a copy keeps the type of $key known to the analysers
		$request = openssl_csr_new(['commonName' => $commonName], $keyForRequest, ['digest_alg' => 'sha256']);
		assert($request instanceof OpenSSLCertificateSigningRequest);
		$certificate = openssl_csr_sign($request, null, $key, $days, ['digest_alg' => 'sha256']);
		assert($certificate instanceof OpenSSLCertificate);
		// Making the key and the request leaves OpenSSL's error queue full of noise about config files it looked for,
		// which OpenSslException would collect into the message of an unrelated failure, so drain it here
		while (openssl_error_string() !== false) {
		}
		return $certificate;
	}

}
