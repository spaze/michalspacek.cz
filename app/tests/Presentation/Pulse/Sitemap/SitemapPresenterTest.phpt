<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\Presentation\Pulse\Sitemap;

use MichalSpacekCz\Http\FetchMetadata\FetchMetadataHeader;
use MichalSpacekCz\Http\FetchMetadata\ResourceIsolationPolicy;
use MichalSpacekCz\Test\Application\ApplicationPresenter;
use MichalSpacekCz\Test\Application\LocaleLinkGeneratorMock;
use MichalSpacekCz\Test\Http\Request as HttpRequestMock;
use MichalSpacekCz\Test\Http\Response as HttpResponseMock;
use MichalSpacekCz\Test\TestCaseRunner;
use Nette\Application\Request;
use Nette\Application\Responses\TextResponse;
use Nette\Application\UI\Presenter;
use Nette\Http\IRequest;
use Nette\Http\IResponse;
use Override;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../../../bootstrap.php';

/** @testCase */
final class SitemapPresenterTest extends TestCase
{

	private ?TextResponse $response = null;


	public function __construct(
		private readonly ApplicationPresenter $applicationPresenter,
		private readonly LocaleLinkGeneratorMock $localeLinkGenerator,
		private readonly HttpRequestMock $httpRequest,
		private readonly HttpResponseMock $httpResponse,
		private readonly ResourceIsolationPolicy $resourceIsolationPolicy,
	) {
		$httpRequest->setMethod(IRequest::Get);
	}


	#[Override]
	protected function setUp(): void
	{
		$this->localeLinkGenerator->setLinksForLocaleParams(['cs_CZ' => 'https://pulse.rizek.test/page']);
	}


	#[Override]
	protected function tearDown(): void
	{
		$this->localeLinkGenerator->reset();
		$this->httpRequest->reset();
		$this->httpRequest->setMethod(IRequest::Get);
		$this->httpResponse->reset();
		$this->response = null;
	}


	public function testDefault(): void
	{
		$this->runPresenter();
		assert($this->response !== null);
		$xml = $this->response->getSource();
		assert(is_string($xml));
		Assert::contains('<url><loc>https://pulse.rizek.test/page</loc></url>', $xml);
		$destinations = $this->localeLinkGenerator->getLinksForLocaleParamsDestinations();
		Assert::notSame([], $destinations);
		Assert::same([], array_values(array_filter($destinations, fn(string $destination): bool => !str_starts_with($destination, 'Pulse:'))), 'Only the Pulse pages');
		Assert::same('application/xml; charset=utf-8', $this->httpResponse->getHeader('Content-Type'));
		Assert::same(IResponse::S200_OK, $this->httpResponse->getCode());
		Assert::notNull($this->httpResponse->getHeader('ETag'));
		Assert::null($this->httpResponse->getHeader('Last-Modified'));
	}


	/**
	 * A crawler's fetch of the sitemap isn't a navigation, and it comes from another site when anything sends one.
	 */
	public function testDefaultCrossSite(): void
	{
		$this->httpRequest->setHeader(FetchMetadataHeader::Site->value, 'cross-site');
		$this->httpRequest->setHeader(FetchMetadataHeader::Mode->value, 'no-cors');
		Assert::true($this->resourceIsolationPolicy->isRequestAllowed($this->runPresenter()));
	}


	private function runPresenter(): Presenter
	{
		$presenter = $this->applicationPresenter->createUiPresenter('Pulse:Sitemap', 'Sitemap', 'default');
		$presenter->autoCanonicalize = false;
		$response = $presenter->run(new Request('Pulse:Sitemap', IRequest::Get, ['action' => 'default']));
		assert($response instanceof TextResponse);
		$this->response = $response;
		return $presenter;
	}

}

TestCaseRunner::run(SitemapPresenterTest::class);
