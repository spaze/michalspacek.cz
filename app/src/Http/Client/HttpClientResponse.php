<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Http\Client;

use MichalSpacekCz\Http\Exceptions\HttpClientTlsCertificateNotAvailableException;
use MichalSpacekCz\Http\Exceptions\HttpClientTlsCertificateNotCapturedException;
use OpenSSLCertificate;

readonly class HttpClientResponse
{

	public function __construct(
		private HttpClientRequest $request,
		private string $body,
		private ?OpenSSLCertificate $tlsCertificate,
	) {
	}


	public function getBody(): string
	{
		return $this->body;
	}


	/**
	 * @throws HttpClientTlsCertificateNotAvailableException
	 * @throws HttpClientTlsCertificateNotCapturedException
	 */
	public function getTlsCertificate(): OpenSSLCertificate
	{
		$scheme = parse_url($this->request->getUrl(), PHP_URL_SCHEME);
		if (!is_string($scheme) || strtolower($scheme) !== 'https') {
			throw new HttpClientTlsCertificateNotAvailableException($this->request->getUrl());
		}
		if (!$this->request->getTlsCaptureCertificate() || !$this->tlsCertificate) {
			throw new HttpClientTlsCertificateNotCapturedException();
		}
		return $this->tlsCertificate;
	}

}
