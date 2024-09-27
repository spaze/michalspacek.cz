<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\Http\FetchMetadata;

use MichalSpacekCz\Test\Application\ApplicationPresenter;
use MichalSpacekCz\Test\Http\Request;
use MichalSpacekCz\Test\Http\Response;
use MichalSpacekCz\Test\NullLogger;
use MichalSpacekCz\Test\PrivateProperty;
use MichalSpacekCz\Test\TestCaseRunner;
use Nette\Application\Application;
use Nette\Application\IPresenter;
use Nette\Application\Request as NetteRequest;
use Nette\Application\UI\Presenter;
use Nette\Http\IRequest;
use Nette\Http\IResponse;
use Nette\Utils\Helpers;
use Override;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../../bootstrap.php';

/** @testCase */
class ResourceIsolationPolicyTest extends TestCase
{

	private const string PRESENTER_NAME = 'Www:Homepage';


	public function __construct(
		private readonly Application $application,
		private readonly Request $httpRequest,
		private readonly Response $httpResponse,
		private readonly NullLogger $logger,
		private readonly FetchMetadata $fetchMetadata,
		private readonly ApplicationPresenter $applicationPresenter,
	) {
	}


	#[Override]
	protected function setUp(): void
	{
		$this->httpResponse->setCode(IResponse::S200_OK);
		$this->application->onPresenter[] = function (Application $application, IPresenter $presenter): void {
			if ($presenter instanceof Presenter) {
				$presenter->autoCanonicalize = false;
			}
		};
	}


	#[Override]
	protected function tearDown(): void
	{
		$this->logger->reset();
		$this->application->onPresenter = [];
	}


	public function testNoHeader(): void
	{
		$this->installPolicy(true);
		$this->callPresenterAction();
		Assert::same([], $this->logger->getLogged());
		Assert::same(IResponse::S200_OK, $this->httpResponse->getCode());
	}


	public function testCrossSite(): void
	{
		$this->installPolicy(true);
		$this->httpRequest->setHeader(FetchMetadataHeader::Site->value, 'cross-site');
		$this->callPresenterAction();
		Assert::same(['GET /; action: :Www:Homepage:default; param names: foo, waldo; headers: Sec-Fetch-Dest: [not sent], Sec-Fetch-Mode: [not sent], Sec-Fetch-Site: cross-site, Sec-Fetch-User: [not sent]'], $this->logger->getLogged());
		Assert::same(IResponse::S200_OK, $this->httpResponse->getCode());
	}


	public function testSameSite(): void
	{
		$this->installPolicy(true);
		$this->httpRequest->setHeader(FetchMetadataHeader::Site->value, 'same-site');
		$this->callPresenterAction();
		Assert::same([], $this->logger->getLogged());
		Assert::same(IResponse::S200_OK, $this->httpResponse->getCode());
	}


	public function testNoHeaderEnforcingPolicy(): void
	{
		$this->installPolicy(false);
		$content = $this->callPresenterAction();
		Assert::contains('messages.homepage.aboutme', $content);
		Assert::notContains('messages.forbidden.crossSite', $content);
		Assert::same([], $this->logger->getLogged());
		Assert::same(IResponse::S200_OK, $this->httpResponse->getCode());
	}


	public function testCrossSiteEnforcingPolicy(): void
	{
		$this->installPolicy(false);
		$this->httpRequest->setHeader(FetchMetadataHeader::Site->value, 'cross-site');
		$content = $this->callPresenterAction();
		Assert::notContains('messages.homepage.aboutme', $content);
		Assert::contains('messages.forbidden.crossSite', $content);
		Assert::same([], $this->logger->getLogged());
		Assert::same(IResponse::S403_Forbidden, $this->httpResponse->getCode());
	}


	public function testSameSiteEnforcingPolicy(): void
	{
		$this->installPolicy(false);
		$this->httpRequest->setHeader(FetchMetadataHeader::Site->value, 'same-site');
		$content = $this->callPresenterAction();
		Assert::contains('messages.homepage.aboutme', $content);
		Assert::notContains('messages.forbidden.crossSite', $content);
		Assert::same([], $this->logger->getLogged());
		Assert::same(IResponse::S200_OK, $this->httpResponse->getCode());
	}


	private function installPolicy(bool $readOnly): void
	{
		$this->httpRequest->setMethod(IRequest::Get);
		$presenter = $this->applicationPresenter->createUiPresenter(self::PRESENTER_NAME, 'Foo', 'bar');
		PrivateProperty::setValue($this->application, 'presenter', $presenter);
		$resourceIsolationPolicy = new ResourceIsolationPolicy($this->fetchMetadata, $this->httpRequest, $this->application, $readOnly);
		$resourceIsolationPolicy->install();
	}


	private function callPresenterAction(): string
	{
		return Helpers::capture(function (): void {
			$this->application->processRequest(new NetteRequest(self::PRESENTER_NAME, IRequest::Get, params: ['foo' => 'bar', 'waldo' => 'fred']));
		});
	}

}

TestCaseRunner::run(ResourceIsolationPolicyTest::class);
