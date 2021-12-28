<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Tls;

use MichalSpacekCz\DateTime\Exceptions\CannotParseDateTimeException;
use MichalSpacekCz\Http\HttpStreamContext;
use MichalSpacekCz\Tls\Exceptions\CertificateException;
use MichalSpacekCz\Tls\Exceptions\OpenSslException;

class CertificateGatherer
{

	public function __construct(
		private CertificateFactory $certificateFactory,
		private HttpStreamContext $httpStreamContext,
	) {
	}


	/**
	 * @throws OpenSslException
	 * @throws CertificateException
	 * @throws CannotParseDateTimeException
	 */
	public function fetchCertificate(string $hostname): Certificate
	{
		$url = "https://{$hostname}/";
		$fp = fopen($url, 'r', context: $this->httpStreamContext->create(
			__METHOD__,
			[
				'method' => 'HEAD',
				'follow_location' => 0,
			],
			[
				'capture_peer_cert' => true,
			],
		));
		if (!$fp) {
			throw new CertificateException("Unable to open {$url}");
		}
		$options = stream_context_get_options($fp);
		fclose($fp);
		return $this->certificateFactory->fromObject($options['ssl']['peer_certificate']);
	}

}
