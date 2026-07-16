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
use Nette\Http\IResponse;
use Nette\Security\SimpleIdentity;
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
	}


	#[Override]
	protected function setUp(): void
	{
		$this->httpRequest->setMethod(IRequest::Get);
	}


	#[Override]
	protected function tearDown(): void
	{
		$this->httpRequest->reset();
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


	public function testCrossSiteLogoutIsBlocked(): void
	{
		$this->user->login(new SimpleIdentity(42));
		// No _nss cookie, so the request looks cross-site to the same-origin guard
		$presenter = $this->applicationPresenter->createUiPresenter('Admin:Sign', 'Sign', 'out');
		$presenter->autoCanonicalize = false;
		$response = $presenter->run(new Request('Admin:Sign', IRequest::Get, ['action' => 'out']));

		Assert::true($this->user->isLoggedIn()); // the logout body never ran
		Assert::type(RedirectResponse::class, $response);
		assert($response instanceof RedirectResponse);
		// The CrossOriginRedirectsTo destination; sign/out here would mean the loop is back
		Assert::same('https://admin.rizek.test/', $response->getUrl());
		Assert::same(IResponse::S302_Found, $response->getCode()); // a permanent redirect would teach the browser to skip the logout for good
	}


	public function testSameSiteLogoutSignsUserOut(): void
	{
		$this->user->login(new SimpleIdentity(42));
		$this->httpRequest->setCookie(CookieName::NetteSameSiteCheck->value, '1');
		$presenter = $this->applicationPresenter->createUiPresenter('Admin:Sign', 'Sign', 'out');
		$presenter->autoCanonicalize = false;
		$response = $presenter->run(new Request('Admin:Sign', IRequest::Get, ['action' => 'out']));

		Assert::false($this->user->isLoggedIn()); // the logout body ran
		Assert::type(RedirectResponse::class, $response);
	}

}

TestCaseRunner::run(SignPresenterTest::class);
