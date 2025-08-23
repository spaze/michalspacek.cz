<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Tls;

use MichalSpacekCz\Tls\Exceptions\OpenSslException;
use MichalSpacekCz\Tls\Exceptions\OpenSslX509ParseException;
use OpenSSLCertificate;

final class OpenSsl
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
		$commonName = is_array($info['subject']) ? $info['subject']['commonName'] ?? null : null;
		if (
			!isset($info['validFrom_time_t'], $info['validTo_time_t'], $info['serialNumberHex'])
			|| ($commonName !== null && !is_string($commonName))
			|| !is_int($info['validFrom_time_t'])
			|| !is_int($info['validTo_time_t'])
			|| !is_string($info['serialNumberHex'])
		) {
			throw new OpenSslX509ParseException(serialize($info));
		}
		return new OpenSslX509ParseResult($commonName, $info['validFrom_time_t'], $info['validTo_time_t'], $info['serialNumberHex']);
	}

}
