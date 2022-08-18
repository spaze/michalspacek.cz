<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\Http;

use MichalSpacekCz\Http\Exceptions\HttpStreamException;
use Tester\Assert;
use Tester\TestCase;

$container = require __DIR__ . '/../bootstrap.php';

/** @testCase */
class HttpStreamContextTest extends TestCase
{

	public function __construct(
		private readonly HttpStreamContext $httpStreamContext,
	) {
	}


	public function testCreate(): void
	{
		$hostname = 'example.com';
		$context = $this->httpStreamContext->create(
			'Foo\Bar',
			[
				'method' => 'HEAD',
				'follow_location' => 0,
			],
			[
				"Host: {$hostname}",
			],
			[
				'capture_peer_cert' => true,
				'peer_name' => $hostname,
			],
		);
		$params = stream_context_get_params($context);
		$expected = [
			'ssl' => [
				'capture_peer_cert' => true,
				'peer_name' => $hostname,
			],
			'http' => [
				'method' => 'HEAD',
				'follow_location' => 0,
				'ignore_errors' => true,
				'user_agent' => 'Foo/Bar',
				'header' => [
					"Host: {$hostname}",
				],
			],
		];
		Assert::same($expected, $params['options']);
		Assert::noError(function () use ($params): void {
			call_user_func($params['notification'], 303, STREAM_NOTIFY_SEVERITY_INFO, 'ok', 808);
		});
		Assert::throws(function () use ($params): void {
			call_user_func($params['notification'], 303, STREAM_NOTIFY_SEVERITY_ERR, 'err', 808);
		}, HttpStreamException::class, 'err (303)', 808);
		Assert::throws(function () use ($params): void {
			call_user_func($params['notification'], 418, STREAM_NOTIFY_SEVERITY_ERR, null, 808);
		}, HttpStreamException::class, '¯\_(ツ)_/¯ (418)', 808);
	}

}

(new HttpStreamContextTest(
	$container->getByType(HttpStreamContext::class),
))->run();
