<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\Presentation\Www\Error;

use MichalSpacekCz\Test\Application\ApplicationPresenter;
use MichalSpacekCz\Test\Application\LocaleLinkGeneratorMock;
use MichalSpacekCz\Test\Http\Request as HttpRequestMock;
use MichalSpacekCz\Test\TestCaseRunner;
use Nette\Application\BadRequestException;
use Nette\Application\Request;
use Nette\Application\Responses\TextResponse;
use Nette\Http\IRequest;
use Nette\Http\IResponse;
use Override;
use Stringable;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../../../bootstrap.php';

/** @testCase */
final class ErrorPresenterTest extends TestCase
{

	public function __construct(
		private readonly ApplicationPresenter $applicationPresenter,
		private readonly LocaleLinkGeneratorMock $localeLinkGenerator,
		HttpRequestMock $httpRequest,
	) {
		$httpRequest->setMethod(IRequest::Get);
	}


	#[Override]
	protected function tearDown(): void
	{
		$this->localeLinkGenerator->reset();
	}


	/**
	 * The page that failed would have its alternates, the error page shown in its place has none.
	 */
	public function testDefaultNoAlternates(): void
	{
		$this->localeLinkGenerator->setLinks(['en_US' => 'https://www.burger.test/talks/foo']);
		$this->localeLinkGenerator->setLinksForLocaleParams([
			'cs_CZ' => 'https://www.rizek.test/prednasky/foo',
			'en_US' => 'https://www.burger.test/talks/foo',
		]);
		$presenter = $this->applicationPresenter->createUiPresenter('Www:Error', 'Error', 'default');
		$presenter->autoCanonicalize = false;
		$response = $presenter->run(new Request('Www:Error', Request::Forward, [
			'action' => 'default',
			'exception' => new BadRequestException('Talk not found', IResponse::S404_NotFound),
			'request' => new Request('Www:Talks', IRequest::Get, ['action' => 'talk', 'name' => 'foo']),
		]));
		assert($response instanceof TextResponse);
		$source = $response->getSource();
		assert(is_string($source) || $source instanceof Stringable);
		$html = (string)$source;
		Assert::notContains('hreflang', $html);
		Assert::contains('href="https://www.burger.test/talks/foo"', $html);
	}

}

TestCaseRunner::run(ErrorPresenterTest::class);
