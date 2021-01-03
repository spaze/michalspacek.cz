<?php
/** @noinspection PhpMissingParentConstructorInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\Http;

use MichalSpacekCz\Test\Http\Response;
use MichalSpacekCz\Test\ServicesTrait;
use Spaze\ContentSecurityPolicy\Config;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../bootstrap.php';

/** @testCase */
class SecurityHeadersTest extends TestCase
{

	use ServicesTrait;


	private Response $httpResponse;

	private Config $cspConfig;

	private SecurityHeaders $securityHeaders;


	protected function setUp()
	{
		$this->httpResponse = $this->getHttpResponse();
		$this->cspConfig = $this->getCspConfig();
		$this->securityHeaders = $this->getSecurityHeaders();

		$this->cspConfig->setPolicy([
			'*.*' => [
				'script-src' => [
					"'none'",
					'example.com'
				],
				'form-action' => [
					"'self'",
				],
			],
		]);
	}


	public function testSendHeaders(): void
	{
		$this->securityHeaders->setCsp('Foo', 'bar');
		$this->securityHeaders->setFeaturePolicy([
			'camera' => 'none',
			'geolocation' => null,
			'midi' => [
				'none',
				'self',
				'https://example.com',
			],
		]);
		$this->securityHeaders->sendHeaders();
		$expected = [
			'content-security-policy' => "script-src 'none' example.com; form-action 'self'",
			'feature-policy' => "camera 'none'; geolocation 'none'; midi 'none' 'self' https://example.com",
		];
		Assert::same($expected, $this->httpResponse->getHeaders());
	}

}

(new SecurityHeadersTest())->run();
