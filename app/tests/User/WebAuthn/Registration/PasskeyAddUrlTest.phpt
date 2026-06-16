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
use MichalSpacekCz\User\WebAuthn\Exceptions\PasskeyRegistrationDisabledException;
use MichalSpacekCz\User\WebAuthn\PasskeyAddTokens;
use MichalSpacekCz\User\WebAuthn\Registration\Exceptions\PasskeyRegistrationUrlArgsException;
use MichalSpacekCz\User\WebAuthn\Registration\Exceptions\PasskeyRegistrationUrlUserNotFoundException;
use Nette\CommandLine\Parser;
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
		return new PasskeyAddUrl($manager, $addTokens, $this->linkGenerator, $cliArgs);
	}


	public function testGenerateThrowsOnArgsError(): void
	{
		Assert::exception(function (): void {
			$this->getPasskeyAddUrl(new CliArgs(['username' => 'waldo'], 'Unknown argument --foo'), false)->generate();
		}, PasskeyRegistrationUrlArgsException::class, 'Unknown argument --foo');
	}


	public function testGenerateThrowsWhenDisabled(): void
	{
		Assert::exception(function (): void {
			$this->getPasskeyAddUrl(new CliArgs(['username' => 'waldo'], null), false)->generate();
		}, PasskeyRegistrationDisabledException::class);
	}


	public function testGenerateThrowsWhenUserNotFound(): void
	{
		$e = Assert::exception(function (): void {
			$this->getPasskeyAddUrl(new CliArgs(['username' => 'nobody'], null), true)->generate();
		}, PasskeyRegistrationUrlUserNotFoundException::class);
		assert($e instanceof PasskeyRegistrationUrlUserNotFoundException);
		Assert::same('nobody', $e->getUsername());
	}


	public function testGenerateReturnsUrl(): void
	{
		$this->database->addFetchFieldResult(1337);
		$url = $this->getPasskeyAddUrl(new CliArgs(['username' => 'leet'], null), true)->generate();
		Assert::contains('/passkeys/add#', $url);
	}


	public function testDefineArgs(): void
	{
		$parser = new Parser();
		PasskeyAddUrl::defineArgs($parser);
		Assert::same(['username' => 'leet'], $parser->parse(['leet']));
		Assert::exception(function () use ($parser): void {
			$parser->parse([]);
		}, Exception::class, 'Missing required argument <username>.');
	}

}

TestCaseRunner::run(PasskeyAddUrlTest::class);
