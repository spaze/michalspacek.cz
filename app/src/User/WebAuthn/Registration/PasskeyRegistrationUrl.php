<?php
declare(strict_types = 1);

namespace MichalSpacekCz\User\WebAuthn\Registration;

use MichalSpacekCz\Application\Cli\CliArgs;
use MichalSpacekCz\Application\Cli\CliArgsProvider;
use MichalSpacekCz\User\Manager;
use MichalSpacekCz\User\SecurityActivity\SecurityEventLogger;
use MichalSpacekCz\User\SecurityActivity\SecurityEventType;
use MichalSpacekCz\User\WebAuthn\Registration\Exceptions\PasskeyRegistrationDisabledException;
use MichalSpacekCz\User\WebAuthn\Registration\Exceptions\PasskeyRegistrationUrlUserNotFoundException;
use Nette\Application\LinkGenerator;
use Nette\CommandLine\Command;
use Override;

abstract readonly class PasskeyRegistrationUrl implements CliArgsProvider
{

	private const string ARG_USERNAME = 'username';


	/**
	 * @return string The presenter:action destination the token's registration link points to
	 */
	abstract protected function getDestination(): string;


	/**
	 * @return SecurityEventType The event recorded when a link of this kind is generated
	 */
	abstract protected function getInitiatedEventType(): SecurityEventType;


	public function __construct(
		private Manager $authenticator,
		private PasskeyRegistrationTokens $tokens,
		private LinkGenerator $linkGenerator,
		private CliArgs $cliArgs,
		private SecurityEventLogger $securityEventLogger,
	) {
	}


	/**
	 * @throws PasskeyRegistrationDisabledException
	 * @throws PasskeyRegistrationUrlUserNotFoundException
	 */
	public function generate(): string
	{
		if (!$this->tokens->isEnabled()) {
			throw new PasskeyRegistrationDisabledException();
		}

		$username = $this->cliArgs->getArg(self::ARG_USERNAME);
		$userId = $this->authenticator->getUserIdByUsername($username);
		if ($userId === null) {
			throw new PasskeyRegistrationUrlUserNotFoundException($username);
		}

		$selectorToken = $this->tokens->create($userId);
		$this->securityEventLogger->record($userId, $this->getInitiatedEventType());
		return $this->linkGenerator->link($this->getDestination() . '#' . $selectorToken);
	}


	#[Override]
	public static function defineArgs(Command $command): void
	{
		$command->addArgument(self::ARG_USERNAME, 'The user the link is generated for');
	}

}
