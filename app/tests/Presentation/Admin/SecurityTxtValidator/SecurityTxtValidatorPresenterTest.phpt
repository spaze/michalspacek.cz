<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\Presentation\Admin\SecurityTxtValidator;

use DateTime;
use DateTimeImmutable;
use MichalSpacekCz\SecurityTxtValidator\SecurityTxtLibraryVersion;
use MichalSpacekCz\Test\Application\ApplicationPresenter;
use MichalSpacekCz\Test\Database\Database;
use MichalSpacekCz\Test\DateTime\DateTimeMachineFactoryUtc;
use MichalSpacekCz\Test\Http\Request as HttpRequestMock;
use MichalSpacekCz\Test\NoOpTranslator;
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
final class SecurityTxtValidatorPresenterTest extends TestCase
{

	public function __construct(
		private readonly ApplicationPresenter $applicationPresenter,
		private readonly User $user,
		private readonly Database $database,
		private readonly SecurityTxtLibraryVersion $libraryVersion,
		private readonly DateTimeMachineFactoryUtc $dateTimeFactory,
		private readonly NoOpTranslator $translator,
		HttpRequestMock $httpRequest,
	) {
		$httpRequest->setMethod(IRequest::Get);
	}


	#[Override]
	protected function tearDown(): void
	{
		$this->user->logout();
		$this->database->reset();
		$this->dateTimeFactory->setDateTime(null);
	}


	private function renderPage(string $action): string
	{
		$this->user->login(new SimpleIdentity(42));
		$presenter = $this->applicationPresenter->createUiPresenter('Admin:SecurityTxtValidator', 'SecurityTxtValidator', $action);
		$presenter->autoCanonicalize = false;
		$response = $presenter->run(new Request('Admin:SecurityTxtValidator', IRequest::Get, ['action' => $action]));
		assert($response instanceof TextResponse);
		$html = $response->getSource();
		assert(is_string($html) || $html instanceof \Stringable);
		return (string)$html;
	}


	/**
	 * The section has a page of its own for the breadcrumbs to lead to, so a page added later gets a parent that
	 * does not have to be one of its siblings.
	 */
	public function testTheRootPageLeadsToTheVersionPage(): void
	{
		$html = $this->renderPage('default');
		Assert::contains('Versions of', $html);
		Assert::contains(SecurityTxtLibraryVersion::PACKAGE_NAME, $html);
	}


	/**
	 * Before the first check there is no Lambda version to compare, which is neither a match nor a mismatch, and the
	 * page must not report a mismatch it has not seen.
	 */
	public function testNoCheckYetIsNeitherAMatchNorAMismatch(): void
	{
		$html = $this->renderPage('lambdaVersion');
		Assert::contains('has not been checked yet', $html);
		Assert::notContains('Versions match', $html);
		Assert::notContains("Versions don't match", $html);
	}


	public function testTheInstalledVersionOnLambdaIsAMatch(): void
	{
		$installed = $this->libraryVersion->getInstalled();
		$this->database->addFetchResult(['id' => 1, 'lastCheck' => new DateTime('2026-10-10 01:45:28 UTC'), 'lambdaVersion' => $installed->getVersion(), 'lambdaReference' => $installed->getReference()]);
		$this->dateTimeFactory->setDateTime(new DateTimeImmutable('2026-10-10 01:48:50 UTC'));
		$html = $this->renderPage('lambdaVersion');
		Assert::contains('Versions match', $html);
		Assert::notContains("Versions don't match", $html);
		Assert::notContains('has not been checked yet', $html);
		Assert::contains('messages.timeIntervalAgo.minutes', $html);
		Assert::same([3], $this->translator->getParameters('messages.timeIntervalAgo.minutes')[0]);
	}


	public function testAnotherVersionOnLambdaIsAMismatch(): void
	{
		$this->database->addFetchResult(['id' => 1, 'lastCheck' => new DateTime('-1 day'), 'lambdaVersion' => '0.0.1', 'lambdaReference' => 'v0.0.1']);
		$html = $this->renderPage('lambdaVersion');
		Assert::contains("Versions don't match", $html);
		Assert::notContains('Versions match', $html);
		Assert::notContains('has not been checked yet', $html);
	}

}

TestCaseRunner::run(SecurityTxtValidatorPresenterTest::class);
