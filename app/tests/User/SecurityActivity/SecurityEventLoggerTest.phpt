<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\User\SecurityActivity;

use Exception;
use MichalSpacekCz\Test\Database\Database;
use MichalSpacekCz\Test\Http\Request;
use MichalSpacekCz\Test\TestCaseRunner;
use Override;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../../bootstrap.php';

/** @testCase */
final class SecurityEventLoggerTest extends TestCase
{

	private const string INSERT = 'INSERT INTO security_events';


	public function __construct(
		private readonly Database $database,
		private readonly SecurityEventLogger $securityEventLogger,
		private readonly Request $httpRequest,
	) {
	}


	#[Override]
	protected function tearDown(): void
	{
		$this->database->reset();
		$this->httpRequest->reset();
	}


	public function testRecordStoresEventWithEncryptedDetails(): void
	{
		$this->httpRequest->setRemoteAddress('1.2.3.4');
		$this->httpRequest->setHeader('User-Agent', 'TestBrowser/1.0');

		// '@' can't occur in the base64url ciphertext
		$this->securityEventLogger->record(42, SecurityEventType::SignInSuccess, ['username' => 'spaze@test']);

		$params = $this->database->getParamsArrayForQuery(self::INSERT);
		Assert::count(1, $params);
		Assert::same(42, $params[0]['key_user']);
		Assert::same('signin.success', $params[0]['action']);
		Assert::same('1.2.3.4', $params[0]['ip']);
		Assert::same('TestBrowser/1.0', $params[0]['user_agent']);
		Assert::type('string', $params[0]['created']);
		Assert::type('string', $params[0]['created_timezone']);
		$details = $params[0]['details'];
		assert(is_string($details));
		Assert::notContains('@', $details);
	}


	public function testRecordWithoutDetailsStoresNull(): void
	{
		$this->securityEventLogger->record(42, SecurityEventType::PasskeyAddInitiated);

		$params = $this->database->getParamsArrayForQuery(self::INSERT);
		Assert::same('passkey.add.initiated', $params[0]['action']);
		Assert::null($params[0]['details']);
	}


	public function testRecordIsFailOpenSoItCannotBreakTheTriggeringAction(): void
	{
		$this->database->willThrow(new Exception('database is down'));
		Assert::noError(function (): void {
			$this->securityEventLogger->record(42, SecurityEventType::EmailChanged, ['to' => 'new@example.com']);
		});
	}

}

TestCaseRunner::run(SecurityEventLoggerTest::class);
