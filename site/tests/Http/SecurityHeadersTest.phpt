<?php
/** @noinspection PhpMissingParentConstructorInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\Http;

use MichalSpacekCz\Test\Http\Response;
use MichalSpacekCz\Test\Http\SecurityHeadersFactory;
use Spaze\ContentSecurityPolicy\CspConfig;
use Tester\Assert;
use Tester\TestCase;

$runner = require __DIR__ . '/../bootstrap.php';

/** @testCase */
class SecurityHeadersTest extends TestCase
{

	public function __construct(
		private readonly Response $httpResponse,
		private readonly CspConfig $cspConfig,
		private readonly SecurityHeadersFactory $securityHeadersFactory,
	) {
	}


	public function testSendHeaders(): void
	{
		$this->cspConfig->setPolicy([
			'*.*' => [
				'script-src' => [
					"'none'",
					'example.com',
				],
				'form-action' => [
					"'self'",
				],
			],
		]);

		$securityHeaders = $this->securityHeadersFactory->create([
			'camera' => 'none',
			'geolocation' => '',
			'midi' => [
				'self',
				'none',
				' ',
				'https://example.com',
			],
		]);
		$securityHeaders->setCsp('Foo', 'bar');
		$securityHeaders->sendHeaders();
		$expected = [
			'content-security-policy' => "script-src 'none' example.com; form-action 'self'",
			'permissions-policy' => 'camera=(), geolocation=(), midi=(self "https://example.com")',
		];
		Assert::same($expected, $this->httpResponse->getHeaders());
	}

}

$runner->run(SecurityHeadersTest::class);
