<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Http\Client;

use MichalSpacekCz\Http\Exceptions\HttpClientTlsCertificateNotAvailableException;
use MichalSpacekCz\Http\Exceptions\HttpClientTlsCertificateNotCapturedException;
use OpenSSLCertificate;
use Uri\WhatWg\Url;

final class HttpClientResponse
{

	/**
	 * @var array<lowercase-string, list<string>>|null
	 */
	private ?array $normalizedHeaders = null;


	/**
	 * @param list<string> $headers Raw response header lines like "Foo: bar"
	 */
	public function __construct(
		private readonly HttpClientRequest $request,
		private readonly string $body,
		private readonly ?OpenSSLCertificate $tlsCertificate,
		private readonly array $headers,
	) {
	}


	public function getBody(): string
	{
		return $this->body;
	}


	public function getHeader(string $name): ?string
	{
		$this->normalizeHeaders();
		$name = strtolower($name);
		return isset($this->normalizedHeaders[$name][0]) ? $this->normalizedHeaders[$name][0] : null;
	}


	/**
	 * @return list<string>|null
	 */
	public function getAllHeaders(string $name): ?array
	{
		$this->normalizeHeaders();
		return $this->normalizedHeaders[strtolower($name)] ?? null;
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


	private function normalizeHeaders(): void
	{
		if ($this->normalizedHeaders === null) {
			$this->normalizedHeaders = [];
			foreach ($this->headers as $header) {
				if (str_starts_with($header, 'HTTP/')) {
					$this->normalizedHeaders = []; // a status line starts a new response; when redirects are followed keep only the last one's headers
					continue;
				}
				$parts = explode(':', $header, 2);
				if (isset($parts[1])) {
					$name = strtolower($parts[0]);
					if (!isset($this->normalizedHeaders[$name])) {
						$this->normalizedHeaders[$name] = [];
					}
					$this->normalizedHeaders[$name][] = trim($parts[1]);
				}
			}
		}
	}

}
