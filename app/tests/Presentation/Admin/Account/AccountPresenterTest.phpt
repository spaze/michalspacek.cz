<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\Presentation\Admin\Account;

use MichalSpacekCz\Http\SecurityHeaders\PermissionsPolicy\PermissionsPolicyDirective;
use MichalSpacekCz\Http\SecurityHeaders\PermissionsPolicy\PermissionsPolicyOrigin;
use MichalSpacekCz\Presentation\Www\BasePresenter;
use MichalSpacekCz\Test\Application\ApplicationPresenter;
use MichalSpacekCz\Test\Http\Request as HttpRequestMock;
use MichalSpacekCz\Test\TestCaseRunner;
use Nette\Application\Request;
use Nette\Http\IRequest;
use Nette\Security\SimpleIdentity;
use Nette\Security\User;
use Override;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../../../bootstrap.php';

/**
 * The in-place passkey prompt on the email form needs the PublicKeyCredentialsGet permissions policy, and
 * it has to be added from the presenter, not the form: the Permissions-Policy header is built before the
 * template (and the form) renders, so a policy added from the form's onRender would be too late. This pins
 * it to the presenter so it can't silently drift back.
 *
 * @testCase
 */
final class AccountPresenterTest extends TestCase
{

	public function __construct(
		private readonly ApplicationPresenter $applicationPresenter,
		private readonly User $user,
		HttpRequestMock $httpRequest,
	) {
		$httpRequest->setMethod(IRequest::Get);
	}


	#[Override]
	protected function tearDown(): void
	{
		$this->user->logout();
	}


	public function testRenderAllowsThePasskeyPrompt(): void
	{
		$this->user->login(new SimpleIdentity(42));

		$presenter = $this->applicationPresenter->createUiPresenter('Admin:Account', 'Account', 'default');
		$presenter->autoCanonicalize = false; // otherwise run() redirects to canonicalize the URL before reaching renderDefault
		$presenter->run(new Request('Admin:Account', IRequest::Get, ['action' => 'default']));

		assert($presenter instanceof BasePresenter);
		$policy = $presenter->getPermissionsPolicy();
		Assert::contains(PermissionsPolicyOrigin::Self, $policy[PermissionsPolicyDirective::PublicKeyCredentialsGet->value] ?? []);
	}

}

TestCaseRunner::run(AccountPresenterTest::class);
