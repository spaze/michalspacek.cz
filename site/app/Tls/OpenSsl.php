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
	 * @return array{subject:array{commonName:string}, validFrom_time_t:int, validTo_time_t:int, serialNumberHex:string}
	 * @throws OpenSslException
	 * @throws OpenSslX509ParseException
	 */
	public static function x509parse(OpenSSLCertificate|string $certificate): array
	{
		$info = @openssl_x509_parse($certificate, false); // intentionally @, warning converted to exception
		if ($info === false) {
			throw new OpenSslException();
		}
		if (!isset($info['subject'], $info['subject']['commonName'], $info['validFrom_time_t'], $info['validTo_time_t'], $info['serialNumberHex'])) {
			throw new OpenSslX509ParseException(serialize($info));
		}
		return $info;
	}

}
