<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\Presentation\Admin\Sign;

use MichalSpacekCz\Http\Cookies\CookieName;
use MichalSpacekCz\Test\Application\ApplicationPresenter;
use MichalSpacekCz\Test\Database\Database;
use MichalSpacekCz\Test\Http\Request as HttpRequestMock;
use MichalSpacekCz\Test\TestCaseRunner;
use MichalSpacekCz\User\SecurityActivity\SecurityEventType;
use Nette\Application\Request;
use Nette\Application\Responses\RedirectResponse;
use Nette\Http\IRequest;
use Nette\Security\User;
use Override;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../../../bootstrap.php';

/**
 * A valid permanent-login (remember-me) cookie signs the user back in without an interactive passkey
 * ceremony. That auto-resume is a security-relevant sign-in and must be recorded in the security log,
 * with its own event type so it stays distinguishable from an interactive sign-in (a stolen cookie
 * manifests as an auto-resume, never as an interactive one).
 *
 * @testCase
 */
final class SignPresenterTest extends TestCase
{

	public function __construct(
		private readonly ApplicationPresenter $applicationPresenter,
		private readonly Database $database,
		private readonly HttpRequestMock $httpRequest,
		private readonly User $user,
	) {
		$this->httpRequest->setMethod(IRequest::Get);
	}


	#[Override]
	protected function tearDown(): void
	{
		$this->database->reset();
		$this->user->logout();
	}


	public function testPermanentLoginResumeIsLogged(): void
	{
		$token = 'sometoken';
		$this->database->setFetchDefaultResult([
			'id' => 1,
			'token' => hash('sha512', $token),
			'userId' => 42,
			'username' => 'spaze',
		]);
		$this->httpRequest->setCookie(CookieName::PermanentLogin->value, "selector:{$token}");

		$presenter = $this->applicationPresenter->createUiPresenter('Admin:Sign', 'Sign', 'in');
		$presenter->autoCanonicalize = false;
		$response = $presenter->run(new Request('Admin:Sign', IRequest::Get, ['action' => 'in']));

		Assert::type(RedirectResponse::class, $response);

		$inserts = $this->database->getParamsArrayForQuery('INSERT INTO security_events');
		Assert::count(1, $inserts);
		Assert::same(SecurityEventType::SignInPermanent->value, $inserts[0]['action']);
		Assert::same(42, $inserts[0]['key_user']);
		Assert::notNull($inserts[0]['details']);
	}


	public function testPlainVisitWithoutCookieLogsNothing(): void
	{
		$presenter = $this->applicationPresenter->createUiPresenter('Admin:Sign', 'Sign', 'in');
		$presenter->autoCanonicalize = false;
		$presenter->run(new Request('Admin:Sign', IRequest::Get, ['action' => 'in']));

		Assert::count(0, $this->database->getParamsArrayForQuery('INSERT INTO security_events'));
	}

}

TestCaseRunner::run(SignPresenterTest::class);
