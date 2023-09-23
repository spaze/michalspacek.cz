<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Http\Client;

use MichalSpacekCz\Http\Exceptions\HttpClientGetException;
use MichalSpacekCz\Http\Exceptions\HttpStreamException;

class HttpClient
{

	/**
	 * @param array<string, string|int> $httpOptions
	 * @param array<int, string> $httpHeaders
	 * @param array<string, string|bool> $sslOptions
	 * @return resource
	 */
	public function createStreamContext(?string $userAgent = null, array $httpOptions = [], array $httpHeaders = [], array $sslOptions = [])
	{
		$httpOptions += [
			'ignore_errors' => true, // To suppress PHP Warning: [...] HTTP/1.0 500 Internal Server Error
			'header' => $httpHeaders,
		];
		if ($userAgent) {
			$httpOptions += [
				'user_agent' => str_replace('\\', '/', $userAgent),
			];
		}
		return stream_context_create(
			[
				'ssl' => $sslOptions,
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
	 * @throws HttpClientGetException
	 */
	public function get(HttpClientRequest $request): string
	{
		$context = $this->createStreamContext(userAgent: $request->getUserAgent(), httpHeaders: $request->getHeaders());
		return $this->request($request, $context);
	}


	/**
	 * @param array<string, string> $formData
	 * @throws HttpClientGetException
	 */
	public function postForm(HttpClientRequest $request, array $formData = []): string
	{
		$context = $this->createStreamContext(
			$request->getUserAgent(),
			['method' => 'POST', 'content' => http_build_query($formData)],
			['Content-Type: application/x-www-form-urlencoded'] + $request->getHeaders(),
		);
		return $this->request($request, $context);
	}


	/**
	 * @param HttpClientRequest $request
	 * @param resource $context
	 * @return string
	 * @throws HttpClientGetException
	 * @noinspection PhpRedundantCatchClauseInspection A notification callback created by self::createStreamContext() may throw HttpStreamException
	 */
	private function request(HttpClientRequest $request, $context): string
	{
		try {
			$result = file_get_contents($request->getUrl(), false, $context);
		} catch (HttpStreamException $e) {
			throw new HttpClientGetException($request->getUrl(), $e->getCode(), $e);
		}
		if (!$result) {
			throw new HttpClientGetException($request->getUrl());
		}
		return $result;
	}

}
