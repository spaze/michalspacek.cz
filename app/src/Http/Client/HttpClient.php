<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Http\Client;

use MichalSpacekCz\Http\Exceptions\HttpClientRequestException;
use MichalSpacekCz\Http\Exceptions\HttpStreamException;
use OpenSSLCertificate;

class HttpClient
{

	/**
	 * @param array<string, string|int> $httpOptions
	 * @param array<string, string|bool> $tlsOptions
	 * @return resource
	 */
	private function createStreamContext(HttpClientRequest $request, array $httpOptions = [], array $tlsOptions = [])
	{
		$httpOptions = [
			'ignore_errors' => true, // To suppress PHP Warning: [...] HTTP/1.0 500 Internal Server Error
			'header' => $request->getHeaders(),
		] + $httpOptions;
		if ($request->getUserAgent() !== null) {
			$httpOptions = ['user_agent' => str_replace('\\', '/', $request->getUserAgent())] + $httpOptions;
		}
		if ($request->getFollowLocation() !== null) {
			$httpOptions = ['follow_location' => (int)$request->getFollowLocation()] + $httpOptions;
		}
		if ($request->getTlsCaptureCertificate() !== null) {
			$tlsOptions = ['capture_peer_cert' => $request->getTlsCaptureCertificate()] + $tlsOptions;
		}
		if ($request->getTlsServerName() !== null) {
			$tlsOptions = ['peer_name' => $request->getTlsServerName()] + $tlsOptions;
		}
		return stream_context_create(
			[
				'ssl' => $tlsOptions,
				'http' => $httpOptions,
			],
			[
				'notification' => function (int $notificationCode, int $severity, ?string $message, int $messageCode): void {
					if ($severity === STREAM_NOTIFY_SEVERITY_ERR) {
						throw new HttpStreamException($notificationCode, $message, $messageCode);
					}
				},
			],
		);
	}


	/**
	 * @throws HttpClientRequestException
	 */
	public function get(HttpClientRequest $request): HttpClientResponse
	{
		$context = $this->createStreamContext($request);
		return $this->request($request, $context);
	}


	/**
	 * @throws HttpClientRequestException
	 */
	public function head(HttpClientRequest $request): HttpClientResponse
	{
		$context = $this->createStreamContext(
			$request,
			['method' => 'HEAD'],
		);
		return $this->request($request, $context);
	}


	/**
	 * @param array<string, string> $formData
	 * @throws HttpClientRequestException
	 */
	public function postForm(HttpClientRequest $request, array $formData = []): HttpClientResponse
	{
		$request->addHeader('Content-Type', 'application/x-www-form-urlencoded');
		$context = $this->createStreamContext(
			$request,
			['method' => 'POST', 'content' => http_build_query($formData)],
		);
		return $this->request($request, $context);
	}


	/**
	 * @param resource $context
	 * @throws HttpClientRequestException
	 * @noinspection PhpRedundantCatchClauseInspection A notification callback created by self::createStreamContext() may throw HttpStreamException
	 */
	private function request(HttpClientRequest $request, $context): HttpClientResponse
	{
		try {
			$fp = fopen($request->getUrl(), 'r', context: $context);
			if (!$fp) {
				throw new HttpClientRequestException($request->getUrl());
			}
			$result = stream_get_contents($fp);
			$options = stream_context_get_options($fp);
			fclose($fp);
		} catch (HttpStreamException $e) {
			throw new HttpClientRequestException($request->getUrl(), $e->getCode(), $e);
		}
		if ($result === false) {
			throw new HttpClientRequestException($request->getUrl());
		}
		if (is_array($options['ssl'])) {
			$certificate = isset($options['ssl']['peer_certificate']) && $options['ssl']['peer_certificate'] instanceof OpenSSLCertificate
				? $options['ssl']['peer_certificate']
				: null;
		} else {
			$certificate = null;
		}
		return new HttpClientResponse($request, $result, $certificate);
	}

}
