<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\Http\Session;

use MichalSpacekCz\Test\Database\Database;
use MichalSpacekCz\Test\TestCaseRunner;
use Override;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../../bootstrap.php';

/** @testCase */
final class SessionRevokerTest extends TestCase
{

	public function __construct(
		private readonly Database $database,
	) {
	}


	#[Override]
	protected function tearDown(): void
	{
		$this->database->reset();
	}


	public function testRevokeForUserDeletesTheUsersSessions(): void
	{
		(new SessionRevoker($this->database, 'sessions'))->revokeForUser(1337);
		Assert::same(
			['sessions', 1337],
			$this->database->getParamsForQuery('DELETE FROM ?name WHERE key_user = ?'),
		);
	}

}

TestCaseRunner::run(SessionRevokerTest::class);
