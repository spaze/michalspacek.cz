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


	public function clear(User $user): void
	{
		$this->revokeForUser($this->manager->getUserId($user));
		$this->cookies->delete(CookieName::PermanentLogin, $this->authCookiesPath);
	}


	/**
	 * Revoke a user's permanent login (tokens only, no cookie), for revoking a user you are not
	 * signed in as. clear() is the self-logout variant that also drops the current browser cookie.
	 */
	public function revokeForUser(int $userId): void
	{
		$this->tokens->deleteAllForUser($userId, $this->getTokenType());
	}


	/**
	 * @throws Exception
	 */
	public function regenerate(User $user): void
	{
		$value = $this->tokens->replaceForUser($this->manager->getUserId($user), $this->getTokenType());
		$this->setCookie($value);
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
