<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\Presentation\Admin\Info;

use MichalSpacekCz\Test\Application\ApplicationPresenter;
use MichalSpacekCz\Test\Http\Request as HttpRequestMock;
use MichalSpacekCz\Test\TestCaseRunner;
use MichalSpacekCz\User\WebAuthn\Session\PasskeySessionSection;
use Nette\Application\Request;
use Nette\Application\Responses\RedirectResponse;
use Nette\Http\IRequest;
use Nette\Security\SimpleIdentity;
use Nette\Security\User;
use Override;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../../../bootstrap.php';

/**
 * phpinfo() exposes env and config, so the page requires a recent passkey confirmation: a logged-in
 * user who hasn't confirmed recently is sent to the reauth page. The already-confirmed path is
 * covered by ReauthenticationTest.
 *
 * @testCase
 */
final class InfoPresenterTest extends TestCase
{

	public function __construct(
		private readonly ApplicationPresenter $applicationPresenter,
		private readonly User $user,
		private readonly PasskeySessionSection $session,
		HttpRequestMock $httpRequest,
	) {
		$httpRequest->setMethod(IRequest::Get);
	}


	#[Override]
	protected function tearDown(): void
	{
		$this->session->removeAll();
		$this->user->logout();
	}


	public function testStaleSessionIsSentToReauth(): void
	{
		$this->user->login(new SimpleIdentity(42)); // logged in, but identity not confirmed recently

		$presenter = $this->applicationPresenter->createUiPresenter('Admin:Info', 'Info', 'php');
		$response = $presenter->run(new Request('Admin:Info', IRequest::Get, ['action' => 'php']));

		assert($response instanceof RedirectResponse);
		Assert::contains('reauth', $response->getUrl()); // requireReauthentication() sent the user to Admin:Reauth
	}

}

TestCaseRunner::run(InfoPresenterTest::class);
