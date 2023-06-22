<?php
/** @noinspection PhpMissingParentConstructorInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\Http;

use MichalSpacekCz\Test\Http\Response;
use MichalSpacekCz\Test\Http\SecurityHeadersFactory;
use Nette\Application\Application;
use Nette\Application\IPresenterFactory;
use Nette\Application\UI\Presenter;
use Spaze\ContentSecurityPolicy\CspConfig;
use Tester\Assert;
use Tester\TestCase;

$runner = require __DIR__ . '/../bootstrap.php';

/** @testCase */
class SecurityHeadersTest extends TestCase
{

	private SecurityHeaders $securityHeaders;


	public function __construct(
		private readonly Response $httpResponse,
		private readonly CspConfig $cspConfig,
		private readonly SecurityHeadersFactory $securityHeadersFactory,
		private readonly IPresenterFactory $presenterFactory,
		private readonly Application $application,
	) {
		$this->cspConfig->setPolicy([
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

		$this->securityHeaders = $this->securityHeadersFactory->create([
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
		/** @var Presenter $presenter */
		$presenter = $this->presenterFactory->createPresenter('Www:Homepage'); // Has to be a real presenter that extends Ui\Presenter
		/** @noinspection PhpInternalEntityUsedInspection */
		$presenter->setParent(null, 'Foo'); // Set the name and also rename it
		$presenter->changeAction('bar');
		Assert::same(':Foo:bar', $presenter->getAction(true));
		Assert::with($this->application, function () use ($presenter): void {
			/** @noinspection PhpDynamicFieldDeclarationInspection $this is $this->application */
			$this->presenter = $presenter;
		});

		$this->securityHeaders->sendHeaders();
		$expected = [
			'content-security-policy' => "script-src 'none' example.com; form-action 'self'",
			'permissions-policy' => 'camera=(), geolocation=(), midi=(self "https://example.com")',
		];
		Assert::same($expected, $this->httpResponse->getHeaders());
	}


	public function testSendHeadersImplementsIPresenterGetsDefaultPolicy(): void
	{
		$presenter = $this->presenterFactory->createPresenter('Www:ErrorGeneric'); // Has to be a real presenter implementing IPresenter
		Assert::with($this->application, function () use ($presenter): void {
			/** @noinspection PhpDynamicFieldDeclarationInspection $this is $this->application */
			$this->presenter = $presenter;
		});

		$this->securityHeaders->sendHeaders();
		$expected = [
			'content-security-policy' => "script-src default.example; trusted-types",
			'permissions-policy' => 'camera=(), geolocation=(), midi=(self "https://example.com")',
		];
		Assert::same($expected, $this->httpResponse->getHeaders());
	}

}

$runner->run(SecurityHeadersTest::class);
