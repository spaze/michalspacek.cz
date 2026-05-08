<?php
declare(strict_types = 1);

namespace MichalSpacekCz\User\WebAuthn;

use MichalSpacekCz\Application\Cli\CliArgs;
use MichalSpacekCz\Application\Cli\CliArgsProvider;
use MichalSpacekCz\Application\LinkGenerator;
use MichalSpacekCz\User\Manager;
use MichalSpacekCz\User\WebAuthn\Exceptions\PasskeyResetArgsException;
use MichalSpacekCz\User\WebAuthn\Exceptions\PasskeyResetDisabledException;
use MichalSpacekCz\User\WebAuthn\Exceptions\PasskeyResetUserNotFoundException;
use Nette\CommandLine\Parser;
use Override;

final readonly class PasskeyResetUrl implements CliArgsProvider
{

	private const string ARG_USERNAME = 'username';


	public function __construct(
		private Manager $authenticator,
		private LinkGenerator $linkGenerator,
		private CliArgs $cliArgs,
	) {
	}


	/**
	 * @throws PasskeyResetArgsException
	 * @throws PasskeyResetDisabledException
	 * @throws PasskeyResetUserNotFoundException
	 */
	public function generate(): string
	{
		$error = $this->cliArgs->getError();
		if ($error !== null) {
			throw new PasskeyResetArgsException($error);
		}

		if (!$this->authenticator->isPasskeyResetEnabled()) {
			throw new PasskeyResetDisabledException();
		}

		$username = $this->cliArgs->getArg(self::ARG_USERNAME);
		$userId = $this->authenticator->getUserIdByUsername($username);
		if ($userId === null) {
			throw new PasskeyResetUserNotFoundException($username);
		}

		$selectorToken = $this->authenticator->createPasskeyResetToken($userId);
		return $this->linkGenerator->link('Admin:Sign:passkeyReset#' . $selectorToken);
	}


	#[Override]
	public static function getArgs(): array
	{
		return [];
	}


	#[Override]
	public static function getPositionalArgs(): array
	{
		return [
			self::ARG_USERNAME => [Parser::Argument => true],
		];
	}

}
