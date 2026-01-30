<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\Http\SecurityHeaders;

use MichalSpacekCz\Test\Application\ApplicationPresenter;
use MichalSpacekCz\Test\Http\Response;
use MichalSpacekCz\Test\PrivateProperty;
use MichalSpacekCz\Test\TestCaseRunner;
use Nette\Application\Application;
use Nette\Application\IPresenterFactory;
use Spaze\ContentSecurityPolicy\CspConfig;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../../bootstrap.php';

/** @testCase */
final class SecurityHeadersTest extends TestCase
{

	/**
	 * @var array<string, string>
	 */
	private array $expected = [
		'server' => '<script/src=//xss.sk></script>',
		'x-powered-by' => "<script>document.write('<img src=//xss.sk title=inline_js_is_bad_mkay.gif>');</script>",
		'x-content-type-options' => 'nosniff',
		'x-frame-options' => 'DENY',
		'referrer-policy' => 'no-referrer, strict-origin-when-cross-origin',
		'permissions-policy' => 'accelerometer=(), camera=(), geolocation=(), gyroscope=(), magnetometer=(), microphone=(), midi=(), payment=(), usb=()',
		'integrity-policy' => 'blocked-destinations=(script), endpoints=(default)',
		'report-to' => '{"group":"default","max_age":31536000,"endpoints":[{"url":"https://plz.report-uri.com/a/d/g"}],"include_subdomains":true}',
		'nel' => '{"report_to":"default","max_age":31536000,"include_subdomains":true}',
	];


	public function __construct(
		private readonly Response $httpResponse,
		CspConfig $cspConfig,
		private readonly SecurityHeaders $securityHeaders,
		private readonly IPresenterFactory $presenterFactory,
		private readonly ApplicationPresenter $applicationPresenter,
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
	}


	public function testSendHeadersExtendsUiPresenter(): void
	{
		$presenter = $this->applicationPresenter->createUiPresenter(
			'Www:Homepage', // Has to be a real presenter that extends Ui\Presenter
			'Foo',
			'bar',
		);
		Assert::same(':Foo:bar', $presenter->getAction(true));
		PrivateProperty::setValue($this->application, 'presenter', $presenter);

		$this->securityHeaders->sendHeaders();
		Assert::equal($this->expected + ['content-security-policy' => "script-src 'none' example.com; form-action 'self'"], $this->httpResponse->getHeaders());
	}


	public function testSendHeadersImplementsIPresenterGetsDefaultPolicy(): void
	{
		$presenter = $this->presenterFactory->createPresenter('Www:ErrorGeneric'); // Has to be a real presenter implementing IPresenter
		PrivateProperty::setValue($this->application, 'presenter', $presenter);

		$this->securityHeaders->sendHeaders();
		Assert::equal($this->expected + ['content-security-policy' => "script-src default.example; trusted-types"], $this->httpResponse->getHeaders());
	}

}

TestCaseRunner::run(SecurityHeadersTest::class);
