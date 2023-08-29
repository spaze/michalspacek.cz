<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\Http;

use MichalSpacekCz\Test\Http\Response;
use MichalSpacekCz\Test\Http\SecurityHeadersFactory;
use MichalSpacekCz\Test\PrivateProperty;
use MichalSpacekCz\Test\TestCaseRunner;
use Nette\Application\Application;
use Nette\Application\IPresenterFactory;
use Nette\Application\UI\Presenter;
use Spaze\ContentSecurityPolicy\CspConfig;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../bootstrap.php';

/** @testCase */
class SecurityHeadersTest extends TestCase
{

	private SecurityHeaders $securityHeaders;


	public function __construct(
		private readonly Response $httpResponse,
		CspConfig $cspConfig,
		SecurityHeadersFactory $securityHeadersFactory,
		private readonly IPresenterFactory $presenterFactory,
		private readonly Application $application,
	) {
		$cspConfig->setPolicy([
			'*.*' => [
				'script-src' => [
					'default.example',
				],
				'trusted-types' => [],
			],
			'foo.*' => [
				'script-src' => [
					"'none'",
					'example.com',
				],
				'form-action' => [
					"'self'",
				],
			],
			'errorgeneric.*' => [
				'script-src' => [
					'this will not',
					'be used',
				],
			],
		]);

		$this->securityHeaders = $securityHeadersFactory->create([
			'camera' => 'none',
			'geolocation' => '',
			'midi' => [
				'self',
				'none',
				' ',
				'https://example.com',
			],
		]);
	}


	public function testSendHeadersExtendsUiPresenter(): void
	{
		$presenter = $this->presenterFactory->createPresenter('Www:Homepage'); // Has to be a real presenter that extends Ui\Presenter
		if (!$presenter instanceof Presenter) {
			Assert::fail('Presenter is of a wrong class ' . get_debug_type($presenter));
		} else {
			/** @noinspection PhpInternalEntityUsedInspection */
			$presenter->setParent(null, 'Foo'); // Set the name and also rename it
			$presenter->changeAction('bar');
			Assert::same(':Foo:bar', $presenter->getAction(true));
			PrivateProperty::setValue($this->application, 'presenter', $presenter);

			$this->securityHeaders->sendHeaders();
			$expected = [
				'content-security-policy' => "script-src 'none' example.com; form-action 'self'",
				'permissions-policy' => 'camera=(), geolocation=(), midi=(self "https://example.com")',
			];
			Assert::same($expected, $this->httpResponse->getHeaders());
		}
	}


	public function testSendHeadersImplementsIPresenterGetsDefaultPolicy(): void
	{
		$presenter = $this->presenterFactory->createPresenter('Www:ErrorGeneric'); // Has to be a real presenter implementing IPresenter
		PrivateProperty::setValue($this->application, 'presenter', $presenter);

		$this->securityHeaders->sendHeaders();
		$expected = [
			'content-security-policy' => "script-src default.example; trusted-types",
			'permissions-policy' => 'camera=(), geolocation=(), midi=(self "https://example.com")',
		];
		Assert::same($expected, $this->httpResponse->getHeaders());
	}

}

TestCaseRunner::run(SecurityHeadersTest::class);
