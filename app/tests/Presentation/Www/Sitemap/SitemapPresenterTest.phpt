<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\Presentation\Www\Sitemap;

use MichalSpacekCz\Test\Application\ApplicationPresenter;
use MichalSpacekCz\Test\Application\LocaleLinkGeneratorMock;
use MichalSpacekCz\Test\Http\Request as HttpRequestMock;
use MichalSpacekCz\Test\Http\Response as HttpResponseMock;
use MichalSpacekCz\Test\TestCaseRunner;
use Nette\Application\Request;
use Nette\Application\Response;
use Nette\Application\Responses\TextResponse;
use Nette\Http\IRequest;
use Nette\Http\IResponse;
use Override;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../../../bootstrap.php';

/** @testCase */
final class SitemapPresenterTest extends TestCase
{

	public function __construct(
		private readonly ApplicationPresenter $applicationPresenter,
		private readonly LocaleLinkGeneratorMock $localeLinkGenerator,
		private readonly HttpRequestMock $httpRequest,
		private readonly HttpResponseMock $httpResponse,
	) {
		$httpRequest->setMethod(IRequest::Get);
	}


	#[Override]
	protected function setUp(): void
	{
		$this->localeLinkGenerator->setLinksForLocaleParams(['cs_CZ' => 'https://www.rizek.test/page']);
	}


	#[Override]
	protected function tearDown(): void
	{
		$this->localeLinkGenerator->reset();
		$this->httpRequest->reset();
		$this->httpRequest->setMethod(IRequest::Get);
		$this->httpResponse->reset();
	}


	public function testDefault(): void
	{
		$response = $this->runPresenter();
		assert($response instanceof TextResponse);
		$xml = $response->getSource();
		assert(is_string($xml));
		Assert::contains('<url><loc>https://www.rizek.test/page</loc></url>', $xml);
		Assert::same('application/xml; charset=utf-8', $this->httpResponse->getHeader('Content-Type'));
		Assert::same(IResponse::S200_OK, $this->httpResponse->getCode());
		Assert::notNull($this->httpResponse->getHeader('ETag'));
		Assert::null($this->httpResponse->getHeader('Last-Modified'));
	}


	public function testDefaultNotModified(): void
	{
		$this->runPresenter();
		$etag = $this->httpResponse->getHeader('ETag');
		assert($etag !== null);
		$this->httpResponse->reset();
		$this->httpRequest->setHeader('If-None-Match', $etag);
		$this->runPresenter();
		Assert::same(IResponse::S304_NotModified, $this->httpResponse->getCode());
	}


	private function runPresenter(): Response
	{
		$presenter = $this->applicationPresenter->createUiPresenter('Www:Sitemap', 'Sitemap', 'default');
		$presenter->autoCanonicalize = false;
		return $presenter->run(new Request('Www:Sitemap', IRequest::Get, ['action' => 'default']));
	}

}

TestCaseRunner::run(SitemapPresenterTest::class);
