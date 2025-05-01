<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\Presentation\SecurityTxtValidator\Account;

use MichalSpacekCz\Test\Application\ApplicationPresenter;
use MichalSpacekCz\Test\Http\Request as HttpRequestMock;
use MichalSpacekCz\Test\TestCaseRunner;
use Nette\Application\BadRequestException;
use Nette\Application\Request;
use Nette\Http\IRequest;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../../../bootstrap.php';

/**
 * The account feature is unfinished, and the only thing keeping it off a public domain is the
 * accountFeatureEnabled flag, which defaults to false. Hiding the link in the layout isn't enough on its own,
 * anyone can request the action directly, so the presenter refuses in startup(). This pins that refusal.
 *
 * Delete this test along with the flag once the feature is finished.
 *
 * @testCase
 */
final class AccountPresenterTest extends TestCase
{

	public function __construct(
		private readonly ApplicationPresenter $applicationPresenter,
		HttpRequestMock $httpRequest,
	) {
		$httpRequest->setMethod(IRequest::Get);
	}


	public function testLoginIsNotFoundWhileTheFeatureIsDisabled(): void
	{
		$presenter = $this->applicationPresenter->createUiPresenter('SecurityTxtValidator:Account', 'Account', 'login');
		$presenter->autoCanonicalize = false; // otherwise run() redirects to canonicalize the URL before startup() is reached
		$e = Assert::exception(function () use ($presenter): void {
			$presenter->run(new Request('SecurityTxtValidator:Account', IRequest::Get, ['action' => 'login']));
		}, BadRequestException::class, 'Account feature is not enabled');
		assert($e instanceof BadRequestException);
		Assert::same(404, $e->getHttpCode());
	}

}

TestCaseRunner::run(AccountPresenterTest::class);
