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
final class PasskeyAddUrlTest extends TestCase
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


	private function getPasskeyAddUrl(CliArgs $cliArgs, bool $passkeyAddEnabled): PasskeyAddUrl
	{
		$manager = new Manager($this->typedDatabase, $this->httpRequest, 'users');
		$addTokens = new PasskeyAddTokens(new UserAuthTokens($this->database, 'users'), $this->dateTimeFactory, $passkeyAddEnabled, '5 minutes');
		return new PasskeyAddUrl($manager, $addTokens, $this->linkGenerator, $cliArgs, $this->securityEventLogger);
	}


	public function testGenerateThrowsOnArgsError(): void
	{
		$cliArgs = CliArgs::parse(PasskeyAddUrl::class, ['waldo', '--foo']);
		$error = $cliArgs->getError();
		assert($error !== null);
		$e = Assert::exception(function () use ($cliArgs): void {
			$this->getPasskeyAddUrl($cliArgs, false)->generate();
		}, PasskeyRegistrationUrlArgsException::class, $error->getMessage());
		assert($e instanceof PasskeyRegistrationUrlArgsException);
		Assert::same($error, $e->getPrevious());
		Assert::same($error, $e->getParseException());
	}


	public function testGenerateThrowsWhenDisabled(): void
	{
		Assert::exception(function (): void {
			$this->getPasskeyAddUrl(CliArgs::parse(PasskeyAddUrl::class, ['waldo']), false)->generate();
		}, PasskeyRegistrationDisabledException::class);
	}


	public function testGenerateThrowsWhenUserNotFound(): void
	{
		$e = Assert::exception(function (): void {
			$this->getPasskeyAddUrl(CliArgs::parse(PasskeyAddUrl::class, ['nobody']), true)->generate();
		}, PasskeyRegistrationUrlUserNotFoundException::class);
		assert($e instanceof PasskeyRegistrationUrlUserNotFoundException);
		Assert::same('nobody', $e->getUsername());
	}


	public function testGenerateReturnsUrl(): void
	{
		$this->database->addFetchFieldResult(1337);
		$url = $this->getPasskeyAddUrl(CliArgs::parse(PasskeyAddUrl::class, ['leet']), true)->generate();
		Assert::contains('/passkeys/add#', $url);
		Assert::same('passkey.add.initiated', $this->database->getParamsArrayForQuery('INSERT INTO security_events')[0]['action']);
	}


	public function testDefineArgs(): void
	{
		Assert::same('leet', CliArgs::parse(PasskeyAddUrl::class, ['leet'])->getArg('username'));
		$error = CliArgs::parse(PasskeyAddUrl::class, [])->getError();
		assert($error !== null);
		Assert::contains('<username>', $error->getMessage());
	}

}

TestCaseRunner::run(PasskeyAddUrlTest::class);
