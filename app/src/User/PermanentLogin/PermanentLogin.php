<?php
declare(strict_types = 1);

namespace MichalSpacekCz\User\PermanentLogin;

use Exception;
use MichalSpacekCz\Application\LinkGenerator;
use MichalSpacekCz\DateTime\DateTimeFactory;
use MichalSpacekCz\Http\Cookies\CookieName;
use MichalSpacekCz\Http\Cookies\Cookies;
use MichalSpacekCz\User\AuthTokens\UserAuthToken;
use MichalSpacekCz\User\AuthTokens\UserAuthTokenLifetime;
use MichalSpacekCz\User\AuthTokens\UserAuthTokens;
use MichalSpacekCz\User\AuthTokens\UserAuthTokenType;
use MichalSpacekCz\User\Manager;
use MichalSpacekCz\User\SecurityActivity\SecurityEventLogger;
use MichalSpacekCz\User\SecurityActivity\SecurityEventType;
use Nette\Http\Url;
use Nette\Security\User;
use Override;

final readonly class PermanentLogin implements UserAuthTokenLifetime
{

	private string $authCookiesPath;


	public function __construct(
		private UserAuthTokens $tokens,
		private Cookies $cookies,
		private Manager $manager,
		private User $user,
		private SecurityEventLogger $securityEventLogger,
		private DateTimeFactory $dateTimeFactory,
		LinkGenerator $linkGenerator,
		private string $interval,
	) {
		$this->authCookiesPath = (new Url($linkGenerator->link('Admin:Sign:in')))->getPath();
	}


	#[Override]
	public function getTokenType(): UserAuthTokenType
	{
		return UserAuthTokenType::PermanentLogin;
	}


	#[Override]
	public function getTtl(): string
	{
		return $this->interval;
	}


	#[Override]
	public function deleteExpired(): int
	{
		return $this->tokens->deleteExpiredByType($this->getTokenType(), $this->dateTimeFactory->create('-' . $this->getTtl()));
	}


	public function clear(): void
	{
		$this->tokens->deleteAllForUser($this->manager->getUserId($this->user), $this->getTokenType());
		$this->cookies->delete(CookieName::PermanentLogin, $this->authCookiesPath);
	}


	/**
	 * @throws Exception
	 */
	public function regenerate(): void
	{
		$value = $this->tokens->replaceForUser($this->manager->getUserId($this->user), $this->getTokenType());
		$this->setCookie($value);
	}


	/**
	 * Signing in from the permanent-login cookie is deliberately not a reauth, so a step-up reauth is still
	 * required afterwards.
	 *
	 * @throws Exception
	 */
	public function signIn(): bool
	{
		$token = $this->verify();
		if ($token === null) {
			return false;
		}
		$this->user->login($this->manager->getIdentity($token->getUserId(), $token->getUsername()));
		$this->regenerate();
		$this->securityEventLogger->record($token->getUserId(), SecurityEventType::SignInPermanent, ['user' => $token->getUsername()]);
		return true;
	}


	public function verify(): ?UserAuthToken
	{
		$cookie = $this->cookies->getString(CookieName::PermanentLogin) ?? '';
		return $this->tokens->verify($cookie, $this->dateTimeFactory->create('-' . $this->getTtl()), $this->getTokenType());
	}


	public function getCookieLifetime(): string
	{
		return $this->interval;
	}


	private function setCookie(string $value): void
	{
		$this->cookies->set(CookieName::PermanentLogin, $value, $this->interval, $this->authCookiesPath, sameSite: 'Strict');
	}

}
