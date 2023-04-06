<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Tls;

use MichalSpacekCz\Tls\Exceptions\OpenSslException;
use OpenSSLCertificate;

class OpenSsl
{

	/**
	 * @param OpenSSLCertificate|string $certificate An OpenSSLCertificate instance or PEM-encoded certificate content
	 * @return array<string, mixed>
	 * @throws OpenSslException
	 */
	public static function x509parse(OpenSSLCertificate|string $certificate): array
	{
		$info = @openssl_x509_parse($certificate, false); // intentionally @, warning converted to exception
		if ($info === false) {
			throw new OpenSslException();
		}
		return $info;
	}

}
