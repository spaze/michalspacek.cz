<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\SecurityTxtValidator\LambdaVersionCheck;

use DateTime;
use MichalSpacekCz\Application\DependencyVersion;
use MichalSpacekCz\SecurityTxtValidator\SecurityTxtLibraryVersion;
use MichalSpacekCz\Test\Database\Database;
use MichalSpacekCz\Test\NullLogger;
use MichalSpacekCz\Test\TestCaseRunner;
use Override;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../../bootstrap.php';

/** @testCase */
final class SecurityTxtValidatorLambdaVersionCheckTest extends TestCase
{

	public function __construct(
		private readonly Database $database,
		private readonly NullLogger $logger,
		private readonly SecurityTxtLibraryVersion $libraryVersion,
		private readonly SecurityTxtValidatorLambdaVersionCheck $versionCheck,
	) {
	}


	#[Override]
	protected function tearDown(): void
	{
		$this->database->reset();
		$this->logger->reset();
	}


	/**
	 * The very first check has nothing to compare the age against, so it is due, and its answer is the comparison like
	 * any other check's: the admin page shows the success message only on true, and the first check after a deploy
	 * is the one most likely to be run by hand.
	 */
	public function testTheFirstCheckEverIsDueAndAnswersTheComparison(): void
	{
		Assert::true($this->versionCheck->checkResponse($this->libraryVersion->getInstalled(), 3, true));
		$written = $this->database->getParamsArrayForQuery('INSERT INTO version_check');
		Assert::count(1, $written);
		assert(is_string($written[0]['last_check']));
		Assert::match('~^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$~', $written[0]['last_check']);
		Assert::same([], $this->logger->getLogged());
	}


	public function testTheFirstCheckEverReportsAMismatch(): void
	{
		Assert::false($this->versionCheck->checkResponse(new DependencyVersion('0.0.1', 'deadbeef'), 3, true));
		Assert::count(1, $this->database->getParamsArrayForQuery('INSERT INTO version_check'));
		$logged = $this->logger->getLogged();
		Assert::count(1, $logged);
		assert(is_string($logged[0]));
		Assert::contains("don't match", $logged[0]);
		Assert::contains('(manual check)', $logged[0]);
	}


	public function testACheckNotYetDueDoesNothing(): void
	{
		$installed = $this->libraryVersion->getInstalled();
		$this->database->addFetchResult(['id' => 3, 'lastCheck' => new DateTime('-1 day'), 'lambdaVersion' => $installed->getVersion(), 'lambdaReference' => $installed->getReference()]);
		Assert::false($this->versionCheck->checkResponse($installed, 3, false));
		Assert::same([], $this->database->getParamsArrayForQuery('INSERT INTO version_check'));
		Assert::same([], $this->database->getParamsArrayForQuery('UPDATE version_check SET ? WHERE id = ?'));
	}


	public function testADueCheckThatMatchesStampsTheRow(): void
	{
		$installed = $this->libraryVersion->getInstalled();
		$this->database->addFetchResult(['id' => 3, 'lastCheck' => new DateTime('-30 days'), 'lambdaVersion' => $installed->getVersion(), 'lambdaReference' => $installed->getReference()]);
		Assert::true($this->versionCheck->checkResponse($installed, 3, false));
		$updated = $this->database->getParamsArrayForQuery('UPDATE version_check SET ? WHERE id = ?');
		Assert::count(1, $updated);
		Assert::same([3], $this->database->getParamsForQuery('UPDATE version_check SET ? WHERE id = ?'));
	}

}

TestCaseRunner::run(SecurityTxtValidatorLambdaVersionCheckTest::class);
