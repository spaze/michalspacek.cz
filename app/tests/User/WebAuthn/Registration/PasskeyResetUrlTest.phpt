<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\User\WebAuthn\Registration;

use Exception;
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
use Nette\CommandLine\Parser;
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
		Assert::exception(function (): void {
			$this->getPasskeyResetUrl(new CliArgs(['username' => 'waldo'], 'Unknown argument --foo'), false)->generate();
		}, PasskeyRegistrationUrlArgsException::class, 'Unknown argument --foo');
	}


	public function testGenerateThrowsWhenDisabled(): void
	{
		Assert::exception(function (): void {
			$this->getPasskeyResetUrl(new CliArgs(['username' => 'waldo'], null), false)->generate();
		}, PasskeyRegistrationDisabledException::class);
	}


	public function testGenerateThrowsWhenUserNotFound(): void
	{
		$e = Assert::exception(function (): void {
			$this->getPasskeyResetUrl(new CliArgs(['username' => 'nobody'], null), true)->generate();
		}, PasskeyRegistrationUrlUserNotFoundException::class);
		assert($e instanceof PasskeyRegistrationUrlUserNotFoundException);
		Assert::same('nobody', $e->getUsername());
	}


	public function testGenerateReturnsUrl(): void
	{
		$this->database->addFetchFieldResult(1337);
		$url = $this->getPasskeyResetUrl(new CliArgs(['username' => 'leet'], null), true)->generate();
		Assert::contains('/sign/passkey-reset#', $url);
		Assert::same('passkey.reset.initiated', $this->database->getParamsArrayForQuery('INSERT INTO security_events')[0]['action']);
	}


	public function testDefineArgs(): void
	{
		$parser = new Parser();
		PasskeyResetUrl::defineArgs($parser);
		Assert::same(['username' => 'leet'], $parser->parse(['leet']));
		Assert::exception(function () use ($parser): void {
			$parser->parse([]);
		}, Exception::class, 'Missing required argument <username>.');
	}

}

TestCaseRunner::run(PasskeyResetUrlTest::class);
