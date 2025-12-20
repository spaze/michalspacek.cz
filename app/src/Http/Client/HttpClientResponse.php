<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Http\Client;

use MichalSpacekCz\Http\Exceptions\HttpClientTlsCertificateNotAvailableException;
use MichalSpacekCz\Http\Exceptions\HttpClientTlsCertificateNotCapturedException;
use OpenSSLCertificate;
use Uri\WhatWg\Url;

final readonly class HttpClientResponse
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
		$scheme = Url::parse($this->request->getUrl())?->getScheme();
		if ($scheme !== 'https') {
			throw new HttpClientTlsCertificateNotAvailableException($this->request->getUrl());
		}
		if ($this->request->getTlsCaptureCertificate() !== true || $this->tlsCertificate === null) {
			throw new HttpClientTlsCertificateNotCapturedException();
		}
		return $this->tlsCertificate;
	}

}
