<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\User\WebAuthn\Registration;

use MichalSpacekCz\Application\Cli\CliArgs;
use MichalSpacekCz\Application\LinkGenerator;
use MichalSpacekCz\Database\TypedDatabase;
use MichalSpacekCz\DateTime\DateTimeFactory;
use MichalSpacekCz\Test\Database\Database;
use MichalSpacekCz\Test\TestCaseRunner;
use MichalSpacekCz\User\AuthTokens\UserAuthTokens;
use MichalSpacekCz\User\Manager;
use MichalSpacekCz\User\SecurityActivity\SecurityEventLogger;
use MichalSpacekCz\User\WebAuthn\Registration\Exceptions\PasskeyRegistrationDisabledException;
use MichalSpacekCz\User\WebAuthn\Registration\Exceptions\PasskeyRegistrationUrlArgsException;
use MichalSpacekCz\User\WebAuthn\Registration\Exceptions\PasskeyRegistrationUrlUserNotFoundException;
use Nette\Http\IRequest;
use Override;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../../../bootstrap.php';

/** @testCase */
final class PasskeyResetUrlTest extends TestCase
{

	public function __construct(
		private readonly Database $database,
		private readonly TypedDatabase $typedDatabase,
		private readonly IRequest $httpRequest,
		private readonly LinkGenerator $linkGenerator,
		private readonly DateTimeFactory $dateTimeFactory,
		private readonly SecurityEventLogger $securityEventLogger,
	) {
	}


	#[Override]
	protected function tearDown(): void
	{
		$this->database->reset();
	}


	private function getPasskeyResetUrl(CliArgs $cliArgs, bool $passkeyResetEnabled): PasskeyResetUrl
	{
		$manager = new Manager($this->typedDatabase, $this->httpRequest, 'users');
		$resetTokens = new PasskeyResetTokens(new UserAuthTokens($this->database, 'users'), $this->dateTimeFactory, $passkeyResetEnabled, '5 minutes');
		return new PasskeyResetUrl($manager, $resetTokens, $this->linkGenerator, $cliArgs, $this->securityEventLogger);
	}


	public function testGenerateThrowsOnArgsError(): void
	{
		$cliArgs = CliArgs::parse(PasskeyResetUrl::class, ['waldo', '--foo']);
		$error = $cliArgs->getError();
		assert($error !== null);
		$e = Assert::exception(function () use ($cliArgs): void {
			$this->getPasskeyResetUrl($cliArgs, false)->generate();
		}, PasskeyRegistrationUrlArgsException::class, $error->getMessage());
		assert($e instanceof PasskeyRegistrationUrlArgsException);
		Assert::same($error, $e->getPrevious());
		Assert::same($error, $e->getParseException());
	}


	public function testGenerateThrowsWhenDisabled(): void
	{
		Assert::exception(function (): void {
			$this->getPasskeyResetUrl(CliArgs::parse(PasskeyResetUrl::class, ['waldo']), false)->generate();
		}, PasskeyRegistrationDisabledException::class);
	}


	public function testGenerateThrowsWhenUserNotFound(): void
	{
		$e = Assert::exception(function (): void {
			$this->getPasskeyResetUrl(CliArgs::parse(PasskeyResetUrl::class, ['nobody']), true)->generate();
		}, PasskeyRegistrationUrlUserNotFoundException::class);
		assert($e instanceof PasskeyRegistrationUrlUserNotFoundException);
		Assert::same('nobody', $e->getUsername());
	}


	public function testGenerateReturnsUrl(): void
	{
		$this->database->addFetchFieldResult(1337);
		$url = $this->getPasskeyResetUrl(CliArgs::parse(PasskeyResetUrl::class, ['leet']), true)->generate();
		Assert::contains('/sign/passkey-reset#', $url);
		Assert::same('passkey.reset.initiated', $this->database->getParamsArrayForQuery('INSERT INTO security_events')[0]['action']);
	}


	public function testDefineArgs(): void
	{
		Assert::same('leet', CliArgs::parse(PasskeyResetUrl::class, ['leet'])->getArg('username'));
		$error = CliArgs::parse(PasskeyResetUrl::class, [])->getError();
		assert($error !== null);
		Assert::contains('<username>', $error->getMessage());
	}

}

TestCaseRunner::run(PasskeyResetUrlTest::class);
