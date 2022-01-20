<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Http;

use MichalSpacekCz\Http\Exceptions\HttpStreamException;

class HttpStreamContext
{

	/**
	 * @param string $userAgent
	 * @param array<string, string|int> $httpOptions
	 * @param array<string, string> $httpHeaders
	 * @param array<string, string|bool> $sslOptions
	 * @return resource
	 */
	public function create(string $userAgent, array $httpOptions, array $httpHeaders = [], array $sslOptions = [])
	{
		return stream_context_create(
			[
				'ssl' => $sslOptions,
				'http' => $httpOptions + [
					'ignore_errors' => true,
					'user_agent' => HttpHeader::normalizeValue($userAgent),
					'header' => $httpHeaders,
				],
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

}
