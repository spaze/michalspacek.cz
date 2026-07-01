<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\User\SecurityActivity;

use MichalSpacekCz\Test\Database\Database;
use MichalSpacekCz\Test\NullLogger;
use MichalSpacekCz\Test\TestCaseRunner;
use Nette\Security\SimpleIdentity;
use Nette\Security\User;
use Nette\Utils\DateTime;
use Override;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../../bootstrap.php';

/** @testCase */
final class SecurityActivityTest extends TestCase
{

	public function __construct(
		private readonly Database $database,
		private readonly SecurityActivity $securityActivity,
		private readonly SecurityEventLogger $securityEventLogger,
		private readonly User $user,
		private readonly NullLogger $logger,
	) {
	}


	#[Override]
	protected function setUp(): void
	{
		$this->user->login(new SimpleIdentity(42));
	}


	#[Override]
	protected function tearDown(): void
	{
		$this->database->reset();
		$this->user->logout();
		$this->logger->reset();
	}


	public function testGetEventsForCurrentUserEmpty(): void
	{
		Assert::same([], $this->securityActivity->getEventsForCurrentUser());
	}


	public function testGetEventsForCurrentUserParsesRowAndDecryptsDetails(): void
	{
		$this->database->setFetchAllDefaultResult([[
			'action' => 'passkey.renamed',
			'created' => DateTime::from('2026-06-25 10:00:00'),
			'createdTimezone' => 'UTC',
			'ip' => '1.2.3.4',
			'userAgent' => 'TestBrowser/1.0',
			'details' => $this->encryptDetails(['passkey' => 'Yubikey']),
		]]);

		$events = $this->securityActivity->getEventsForCurrentUser();

		Assert::count(1, $events);
		Assert::same(SecurityEventType::PasskeyRenamed, $events[0]->type);
		Assert::same('passkey.renamed', $events[0]->action);
		Assert::same('2026-06-25 10:00:00', $events[0]->created->format('Y-m-d H:i:s'));
		Assert::same('UTC', $events[0]->created->getTimezone()->getName());
		Assert::same('1.2.3.4', $events[0]->ip);
		Assert::same('TestBrowser/1.0', $events[0]->userAgent);
		Assert::same(['passkey' => 'Yubikey'], $events[0]->details);
		Assert::same('messages.account.securityLog.event.passkeyRenamed', $events[0]->labelKey());
	}


	public function testUnknownActionDegradesToUnknownLabel(): void
	{
		$this->database->setFetchAllDefaultResult([[
			'action' => 'passkey.teleported', // a value no current SecurityEventType knows
			'created' => DateTime::from('2026-06-25 10:00:00'),
			'createdTimezone' => 'UTC',
			'ip' => null,
			'userAgent' => null,
			'details' => null,
		]]);

		$events = $this->securityActivity->getEventsForCurrentUser();

		Assert::null($events[0]->type);
		Assert::same('passkey.teleported', $events[0]->action); // the raw action is kept
		Assert::same('messages.account.securityLog.event.unknown', $events[0]->labelKey()); // labelled generically, never throws
		Assert::same([], $events[0]->details);
		Assert::null($events[0]->ip);
		Assert::null($events[0]->userAgent);
	}


	public function testUndecryptableDetailsDegradeInsteadOfBreakingTheLog(): void
	{
		$this->database->setFetchAllDefaultResult([[
			'action' => 'passkey.renamed',
			'created' => DateTime::from('2026-06-25 10:00:00'),
			'createdTimezone' => 'UTC',
			'ip' => null,
			'userAgent' => null,
			'details' => 'not-a-valid-ciphertext', // e.g. a dropped key in real life
		]]);

		$events = $this->securityActivity->getEventsForCurrentUser();

		Assert::count(1, $events); // the row still renders rather than the whole page erroring
		Assert::same([], $events[0]->details);
		Assert::count(1, $this->logger->getLogged()); // but the operator still gets told the details couldn't be decrypted
	}


	public function testRowWithInvalidTimezoneIsSkippedNotFatal(): void
	{
		$this->database->setFetchAllDefaultResult([[
			'action' => 'signin.success',
			'created' => DateTime::from('2026-06-26 10:00:00'),
			'createdTimezone' => 'Not/AReal_Zone',
			'ip' => null,
			'userAgent' => null,
			'details' => null,
		]]);

		Assert::same([], $this->securityActivity->getEventsForCurrentUser()); // bad row skipped, the page survives
		Assert::count(1, $this->logger->getLogged()); // the skipped row is logged for the operator
	}


	/**
	 * @param array<string, string|null> $details
	 */
	private function encryptDetails(array $details): string
	{
		// Produce a real ciphertext the way the logger does, captured from the recorded INSERT params, so the
		// test doesn't need the (type-ambiguous) encryption service injected directly.
		$this->securityEventLogger->record(1, SecurityEventType::PasskeyRenamed, $details);
		$params = $this->database->getParamsArrayForQuery('INSERT INTO security_events');
		$encrypted = $params[0]['details'];
		assert(is_string($encrypted));
		$this->database->reset();
		return $encrypted;
	}

}

TestCaseRunner::run(SecurityActivityTest::class);
