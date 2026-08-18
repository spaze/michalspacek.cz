<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\SecurityTxtValidator;

use MichalSpacekCz\Test\Database\Database;
use MichalSpacekCz\Test\TestCaseRunner;
use Override;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../bootstrap.php';

/** @testCase */
final class SecurityTxtValidatorTest extends TestCase
{

	public function __construct(
		private readonly SecurityTxtValidator $validator,
		private readonly Database $database,
	) {
	}


	#[Override]
	protected function tearDown(): void
	{
		$this->database->reset();
	}


	/**
	 * Anyone can press the clear button, so the age condition is the only thing stopping a host being re-fetched over
	 * and over. It belongs in the statement, and the row has to be found by the same ASCII host the write used, not by
	 * the URL the visitor typed.
	 */
	public function testClearCacheDeletesByAsciiHostAndOnlyOnceOldEnough(): void
	{
		$this->validator->clearCache('https://foó.example/some/path');
		// The whole condition is the needle: drop the age from the statement and this stops matching, which is the
		// point, because an age checked anywhere but in the DELETE leaves a gap between deciding and deleting.
		$params = $this->database->getParamsForQueryContaining('DELETE FROM policy_cache WHERE ascii_host = ? AND last_check_time < ?');
		Assert::count(2, $params); // the host to delete, and the age the cached result has to have reached
		Assert::same('xn--fo-6ja.example', $params[0]);
	}


	public function testClearCacheDeletesNothingForAnUnusableHostname(): void
	{
		$this->validator->clearCache('localhost');
		Assert::same([], $this->database->getParamsForQueryContaining('DELETE FROM policy_cache'));
	}

}

TestCaseRunner::run(SecurityTxtValidatorTest::class);
