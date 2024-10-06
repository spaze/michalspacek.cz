<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Tls;

use MichalSpacekCz\Tls\Exceptions\OpenSslException;
use MichalSpacekCz\Tls\Exceptions\OpenSslX509ParseException;
use OpenSSLCertificate;

class OpenSsl
{

	/**
	 * @param OpenSSLCertificate|string $certificate An OpenSSLCertificate instance or PEM-encoded certificate content
	 * @throws OpenSslException
	 * @throws OpenSslX509ParseException
	 */
	public static function x509parse(OpenSSLCertificate|string $certificate): OpenSslX509ParseResult
	{
		$info = @openssl_x509_parse($certificate, false); // intentionally @, warning converted to exception
		if ($info === false) {
			throw new OpenSslException();
		}
		if (
			!isset($info['subject']['commonName'], $info['validFrom_time_t'], $info['validTo_time_t'], $info['serialNumberHex'])
			|| !is_array($info['subject'])
			|| !is_string($info['subject']['commonName'])
			|| !is_int($info['validFrom_time_t'])
			|| !is_int($info['validTo_time_t'])
			|| !is_string($info['serialNumberHex'])
		) {
			throw new OpenSslX509ParseException(serialize($info));
		}
		return new OpenSslX509ParseResult($info['subject']['commonName'], $info['validFrom_time_t'], $info['validTo_time_t'], $info['serialNumberHex']);
	}

}
