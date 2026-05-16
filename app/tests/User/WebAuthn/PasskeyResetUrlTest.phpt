<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\User\WebAuthn;

use MichalSpacekCz\Application\Cli\CliArgs;
use MichalSpacekCz\Application\LinkGenerator;
use MichalSpacekCz\Database\TypedDatabase;
use MichalSpacekCz\Test\Database\Database;
use MichalSpacekCz\Test\TestCaseRunner;
use MichalSpacekCz\User\AuthTokens\UserAuthTokens;
use MichalSpacekCz\User\Manager;
use MichalSpacekCz\User\WebAuthn\Exceptions\PasskeyResetArgsException;
use MichalSpacekCz\User\WebAuthn\Exceptions\PasskeyResetDisabledException;
use MichalSpacekCz\User\WebAuthn\Exceptions\PasskeyResetUserNotFoundException;
use Nette\CommandLine\Parser;
use Nette\Http\IRequest;
use Override;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../../bootstrap.php';

/** @testCase */
final class PasskeyResetUrlTest extends TestCase
{

	public function __construct(
		private readonly Database $database,
		private readonly TypedDatabase $typedDatabase,
		private readonly IRequest $httpRequest,
		private readonly LinkGenerator $linkGenerator,
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
		$resetTokens = new PasskeyResetTokens(new UserAuthTokens($this->database, 'users'), $passkeyResetEnabled);
		return new PasskeyResetUrl($manager, $resetTokens, $this->linkGenerator, $cliArgs);
	}


	public function testGenerateThrowsOnArgsError(): void
	{
		Assert::exception(function (): void {
			$this->getPasskeyResetUrl(new CliArgs(['username' => 'waldo'], 'Unknown argument --foo'), false)->generate();
		}, PasskeyResetArgsException::class, 'Unknown argument --foo');
	}


	public function testGenerateThrowsWhenDisabled(): void
	{
		Assert::exception(function (): void {
			$this->getPasskeyResetUrl(new CliArgs(['username' => 'waldo'], null), false)->generate();
		}, PasskeyResetDisabledException::class);
	}


	public function testGenerateThrowsWhenUserNotFound(): void
	{
		$e = Assert::exception(function (): void {
			$this->getPasskeyResetUrl(new CliArgs(['username' => 'nobody'], null), true)->generate();
		}, PasskeyResetUserNotFoundException::class);
		assert($e instanceof PasskeyResetUserNotFoundException);
		Assert::same('nobody', $e->getUsername());
	}


	public function testGenerateReturnsUrl(): void
	{
		$this->database->addFetchFieldResult(1337);
		$url = $this->getPasskeyResetUrl(new CliArgs(['username' => 'leet'], null), true)->generate();
		Assert::contains('/sign/passkey-reset#', $url);
	}


	public function testArgs(): void
	{
		Assert::same([], PasskeyResetUrl::getArgs());
		Assert::same(['username' => [Parser::Argument => true]], PasskeyResetUrl::getPositionalArgs());
	}

}

TestCaseRunner::run(PasskeyResetUrlTest::class);
