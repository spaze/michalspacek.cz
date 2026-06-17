<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\User\WebAuthn;

use MichalSpacekCz\Test\Database\Database;
use MichalSpacekCz\Test\TestCaseRunner;
use MichalSpacekCz\User\UserAccessRevoker;
use MichalSpacekCz\User\WebAuthn\Exceptions\PasskeyCredentialToKeepNotFoundException;
use MichalSpacekCz\User\WebAuthn\Exceptions\PasskeyResetRevokeFailedException;
use Override;
use RuntimeException;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../../bootstrap.php';

/** @testCase */
final class PasskeyResetRevokerTest extends TestCase
{

	public function __construct(
		private readonly Database $database,
		private readonly PasskeyStorage $passkeyStorage,
	) {
	}


	#[Override]
	protected function tearDown(): void
	{
		$this->database->reset();
	}


	public function testRevokeDeletesOtherPasskeysAndRunsEveryAccessRevoker(): void
	{
		$this->database->setFetchFieldDefaultResult(1); // the kept credential exists, so the passkey delete runs

		$revoker1 = new class implements UserAccessRevoker {

			public ?int $userId = null;


			#[Override]
			public function revokeForUser(int $userId): void
			{
				$this->userId = $userId;
			}

		};
		$revoker2 = new class implements UserAccessRevoker {

			public ?int $userId = null;


			#[Override]
			public function revokeForUser(int $userId): void
			{
				$this->userId = $userId;
			}

		};

		(new PasskeyResetRevoker($this->passkeyStorage, [$revoker1, $revoker2]))->revoke(42, 'keep-cred-id');

		Assert::same(
			['passkeys', 42, 'keep-cred-id'],
			$this->database->getParamsForQuery('DELETE FROM ?name WHERE key_user = ? AND credential_id != ?'),
		);
		Assert::same(42, $revoker1->userId);
		Assert::same(42, $revoker2->userId);
	}


	public function testRevokeIsBestEffortAndAggregatesFailures(): void
	{
		// No kept credential set up, so the passkey delete throws PasskeyCredentialToKeepNotFoundException.
		$throwing = new class implements UserAccessRevoker {

			public bool $called = false;


			#[Override]
			public function revokeForUser(int $userId): void
			{
				$this->called = true;
				throw new RuntimeException('boom');
			}

		};
		$recording = new class implements UserAccessRevoker {

			public ?int $userId = null;


			#[Override]
			public function revokeForUser(int $userId): void
			{
				$this->userId = $userId;
			}

		};

		$e = Assert::exception(function () use ($throwing, $recording): void {
			(new PasskeyResetRevoker($this->passkeyStorage, [$throwing, $recording]))->revoke(42, 'missing-cred-id');
		}, PasskeyResetRevokeFailedException::class);
		assert($e instanceof PasskeyResetRevokeFailedException);

		Assert::true($throwing->called);
		Assert::same(42, $recording->userId); // ran even though the passkey delete and the earlier revoker threw
		Assert::count(2, $e->getFailedSteps()); // the passkey delete plus the throwing access revoker
		Assert::type(PasskeyCredentialToKeepNotFoundException::class, $e->getFailedSteps()[0]); // the passkey delete runs (and fails) first
		Assert::type(RuntimeException::class, $e->getFailedSteps()[1]);
		Assert::same($e->getFailedSteps()[0], $e->getPrevious()); // the first failure is chained as the cause
		Assert::contains(PasskeyCredentialToKeepNotFoundException::class, $e->getMessage()); // the message names each failed step's class
		Assert::contains(RuntimeException::class, $e->getMessage());
	}

}

TestCaseRunner::run(PasskeyResetRevokerTest::class);
