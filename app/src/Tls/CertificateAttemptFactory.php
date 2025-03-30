<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Tls;

final class CertificateAttemptFactory
{

	/**
	 * @param array<string|int, mixed> $request
	 * @return list<CertificateAttempt>
	 */
	public function listFromLogRequest(array $request): array
	{
		$certs = [];
		foreach ($request as $cert) {
			if (
				is_array($cert)
				&& isset($cert['cn'], $cert['ext'])
				&& is_string($cert['cn'])
				&& is_string($cert['ext'])
			) {
				$certs[] = new CertificateAttempt(
					$cert['cn'],
					$cert['ext'] !== '' ? $cert['ext'] : null,
				);
			}
		}
		return $certs;
	}

}
