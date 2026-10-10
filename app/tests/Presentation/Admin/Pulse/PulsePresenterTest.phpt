<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\Presentation\Admin\Pulse;

use MichalSpacekCz\Test\Application\ApplicationPresenter;
use MichalSpacekCz\Test\Http\Request as HttpRequestMock;
use MichalSpacekCz\Test\TestCaseRunner;
use Nette\Application\Request;
use Nette\Application\Responses\TextResponse;
use Nette\Http\IRequest;
use Nette\Security\SimpleIdentity;
use Nette\Security\User;
use Override;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../../../bootstrap.php';

/** @testCase */
final class PulsePresenterTest extends TestCase
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


	private function renderPage(string $action): string
	{
		$this->user->login(new SimpleIdentity(42));
		$presenter = $this->applicationPresenter->createUiPresenter('Admin:Pulse', 'Pulse', $action);
		$presenter->autoCanonicalize = false;
		$response = $presenter->run(new Request('Admin:Pulse', IRequest::Get, ['action' => $action]));
		assert($response instanceof TextResponse);
		$html = $response->getSource();
		assert(is_string($html) || $html instanceof \Stringable);
		return (string)$html;
	}


	/**
	 * The section has a page of its own for the breadcrumbs to lead to, so a page added later gets a parent that
	 * does not have to be one of its siblings.
	 */
	public function testTheRootPageLeadsToThePasswordsStoragesPage(): void
	{
		$html = $this->renderPage('default');
		Assert::contains('Password storages', $html);
	}


	public function testThePasswordsStoragesPageLeadsBackToTheRootPage(): void
	{
		$html = $this->renderPage('passwordsStorages');
		Assert::contains('Pulse: Password storages', $html);
		Assert::contains('&raquo; <a href="https://admin.rizek.test/pulse">Pulse</a>', $html);
	}

}

TestCaseRunner::run(PulsePresenterTest::class);
