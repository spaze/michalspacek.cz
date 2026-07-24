<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\Application;

use MichalSpacekCz\EasterEgg\CrLfUrlInjections;
use MichalSpacekCz\Http\FetchMetadata\ResourceIsolationPolicy;
use MichalSpacekCz\Http\SecurityHeaders\SecurityHeaders;
use MichalSpacekCz\Test\Application\SpyApplication;
use MichalSpacekCz\Test\Http\Request;
use MichalSpacekCz\Test\Http\Response;
use MichalSpacekCz\Test\TestCaseRunner;
use MichalSpacekCz\User\UserSessionAdditionalData;
use Nette\Http\IResponse;
use Nette\Http\UrlScript;
use Override;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../bootstrap.php';

/** @testCase */
final class WebApplicationTest extends TestCase
{

	public function __construct(
		private readonly WebApplication $webApplication,
		private readonly Request $httpRequest,
		private readonly Response $httpResponse,
		private readonly SecurityHeaders $securityHeaders,
		private readonly CrLfUrlInjections $crLfUrlInjections,
		private readonly ResourceIsolationPolicy $resourceIsolationPolicy,
		private readonly UserSessionAdditionalData $userSessionAdditionalData,
	) {
	}


	#[Override]
	protected function tearDown(): void
	{
		$this->httpResponse->reset();
		ServerEnv::unset('HTTP_HOST');
	}


	public function testRedirectsToCanonicalHostOverHttps(): void
	{
		$application = new SpyApplication();
		$this->httpRequest->setUrl(new UrlScript('http://totally.not.fqdn.example/path/here?query=1'));
		ServerEnv::setString('HTTP_HOST', 'totally.not.fqdn.example');

		$this->createWebApplication($application)->run();

		Assert::false($application->ran); // a wrong host redirects and returns before Application::run()
		Assert::same(IResponse::S301_MovedPermanently, $this->httpResponse->getRedirectCode());
		Assert::same(sprintf('https://%s/path/here?query=1', $this->webApplication->getFqdn()), $this->httpResponse->getRedirectUrl());
	}


	public function testDoesNotRedirectWhenAlreadyOnCanonicalHost(): void
	{
		$application = new SpyApplication();
		$this->httpRequest->setUrl(new UrlScript(sprintf('https://%s/path/here', $this->webApplication->getFqdn())));
		ServerEnv::setString('HTTP_HOST', $this->webApplication->getFqdn());

		$this->createWebApplication($application)->run();

		Assert::true($application->ran); // a canonical host proceeds to Application::run()
		Assert::null($this->httpResponse->getRedirectCode());
		Assert::null($this->httpResponse->getRedirectUrl());
	}


	private function createWebApplication(SpyApplication $application): WebApplication
	{
		return new WebApplication(
			$this->httpRequest,
			$this->httpResponse,
			$this->securityHeaders,
			$application,
			$this->crLfUrlInjections,
			$this->resourceIsolationPolicy,
			$this->userSessionAdditionalData,
			$this->webApplication->getFqdn(),
		);
	}

}

TestCaseRunner::run(WebApplicationTest::class);
