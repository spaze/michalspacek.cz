<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\Http;

use MichalSpacekCz\Http\Client\HttpClient;
use MichalSpacekCz\Http\Client\HttpClientRequest;
use MichalSpacekCz\Http\Exceptions\HttpStreamException;
use MichalSpacekCz\Test\TestCaseRunner;
use ReflectionMethod;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../bootstrap.php';

/** @testCase */
class HttpClientTest extends TestCase
{

	public function __construct(
		private readonly HttpClient $httpClient,
	) {
	}


	public function testCreateStreamContextNotification(): void
	{
		$hostname = 'example.com';
		$request = new HttpClientRequest("https://{$hostname}/");
		$request->setUserAgent('Foo\Bar');
		$request->setFollowLocation(false);
		$request->addHeader('Host', $hostname);
		$request->setTlsCaptureCertificate(true);
		$request->setTlsServerName($hostname);

		$method = new ReflectionMethod($this->httpClient, 'createStreamContext');
		$context = $method->invoke(
			$this->httpClient,
			$request,
			[
				'ignore_errors' => false,
				'method' => 'HEAD',
				'follow_location' => 1,
				'user_agent' => 'overwritten-anyway/1.0',
			],
			[
				'capture_peer_cert' => false,
				'peer_name' => 'will.be.overwritten.anyway',
			],
		);
		if (!is_resource($context)) {
			Assert::fail('Context is of a wrong type ' . get_debug_type($context));
			return;
		}

		$params = stream_context_get_params($context);
		$expected = [
			'ssl' => [
				'peer_name' => $hostname,
				'capture_peer_cert' => true,
			],
			'http' => [
				'follow_location' => 0,
				'user_agent' => 'Foo/Bar',
				'ignore_errors' => true,
				'header' => [
					"Host: {$hostname}",
				],
				'method' => 'HEAD',
			],
		];
		Assert::same($expected, $params['options']);
		assert(is_callable($params['notification']));
		Assert::noError(function () use ($params): void {
			call_user_func($params['notification'], 303, STREAM_NOTIFY_SEVERITY_INFO, 'ok', 808);
		});
		Assert::exception(function () use ($params): void {
			call_user_func($params['notification'], 303, STREAM_NOTIFY_SEVERITY_ERR, 'err', 808);
		}, HttpStreamException::class, 'err (303)', 808);
		Assert::exception(function () use ($params): void {
			call_user_func($params['notification'], 418, STREAM_NOTIFY_SEVERITY_ERR, null, 808);
		}, HttpStreamException::class, '¯\_(ツ)_/¯ (418)', 808);
	}

}

TestCaseRunner::run(HttpClientTest::class);
