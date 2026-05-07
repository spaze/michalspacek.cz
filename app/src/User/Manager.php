<?php
declare(strict_types = 1);

namespace MichalSpacekCz\User;

use DateTimeInterface;
use Exception;
use MichalSpacekCz\Application\LinkGenerator;
use MichalSpacekCz\Database\TypedDatabase;
use MichalSpacekCz\Http\Cookies\CookieName;
use MichalSpacekCz\Http\Cookies\Cookies;
use MichalSpacekCz\User\Exceptions\IdentityIdNotIntException;
use MichalSpacekCz\User\Exceptions\IdentityNotSimpleIdentityException;
use MichalSpacekCz\User\Exceptions\IdentityUsernameNotStringException;
use MichalSpacekCz\User\Exceptions\IdentityWithoutUsernameException;
use MichalSpacekCz\User\WebAuthn\Exceptions\PasskeyResetDisabledException;
use Nette\Database\Explorer;
use Nette\Database\UniqueConstraintViolationException;
use Nette\Http\IRequest;
use Nette\Http\Url;
use Nette\Security\SimpleIdentity;
use Nette\Security\User;
use Nette\Utils\DateTime;
use Nette\Utils\Random;

final readonly class Manager
{

	private const string AUTH_SELECTOR_TOKEN_SEPARATOR = ':';

	private const string RESET_TOKEN_EXPIRY = '5 minutes';

	private string $authCookiesPath;


	public function __construct(
		private Explorer $database,
		private TypedDatabase $typedDatabase,
		private IRequest $httpRequest,
		private Cookies $cookies,
		LinkGenerator $linkGenerator,
		private string $permanentLoginInterval,
		private bool $resetEnabled,
		private string $usersTableName,
	) {
		$this->authCookiesPath = (new Url($linkGenerator->link('Admin:Sign:in')))->getPath();
	}


	public function getIdentity(int $id, string $username): SimpleIdentity
	{
		return new SimpleIdentity($id, [], ['username' => $username]);
	}


	/**
	 * @throws IdentityNotSimpleIdentityException
	 * @throws IdentityWithoutUsernameException
	 * @throws IdentityUsernameNotStringException
	 */
	public function getIdentityUsernameByUser(User $user): string
	{
		$identity = $user->getIdentity();
		if (!$identity instanceof SimpleIdentity) {
			throw new IdentityNotSimpleIdentityException($identity);
		}
		if (!isset($identity->username)) {
			throw new IdentityWithoutUsernameException();
		}
		if (!is_string($identity->username)) {
			throw new IdentityUsernameNotStringException(get_debug_type($identity->username));
		}
		return $identity->username;
	}


	public function getUserIdByUsername(string $username): ?int
	{
		return $this->typedDatabase->fetchFieldIntNullable('SELECT id_user FROM ?name WHERE username = ?', $this->usersTableName, $username);
	}


	public function isForbidden(): bool
	{
		$forbidden = $this->typedDatabase->fetchFieldIntNullable(
			'SELECT
				1
			FROM
				forbidden
			WHERE
				ip = ?',
			$this->httpRequest->getRemoteAddress(),
		);
		return (bool)$forbidden;
	}


	/**
	 * Hash token used for permanent login.
	 *
	 * @return non-empty-string SHA-512 hash of the token
	 */
	private function hashToken(string $token): string
	{
		return hash('sha512', $token);
	}


	/**
	 * Insert authentication token into database.
	 *
	 * Selector and token are regenerated if selector already exists in the table.
	 *
	 * @return non-empty-string Concatenation of selector, separator, token
	 * @throws Exception
	 */
	private function insertToken(int $userId, UserAuthTokenType $type): string
	{
		$selector = Random::generate(32, '0-9a-zA-Z');
		$token = Random::generate(64, '0-9a-zA-Z');

		try {
			$this->database->query(
				'INSERT INTO auth_tokens',
				[
					'key_user' => $userId,
					'selector' => $selector,
					'token' => $this->hashToken($token),
					'created' => new DateTime(),
					'type' => $type->value,
				],
			);
		} catch (UniqueConstraintViolationException) {
			// regenerate the access code and try harder this time
			return $this->insertToken($userId, $type);
		}
		return $selector . self::AUTH_SELECTOR_TOKEN_SEPARATOR . $token;
	}


	/**
	 * @throws IdentityIdNotIntException
	 */
	private function getUserId(User $user): int
	{
		$userId = $user->getId();
		if (!is_int($userId)) {
			throw new IdentityIdNotIntException(get_debug_type($userId));
		}
		return $userId;
	}


	/**
	 * Store permanent login token in database and send a cookie to the browser.
	 *
	 * @throws Exception
	 */
	public function storePermanentLogin(User $user): void
	{
		$value = $this->insertToken($this->getUserId($user), UserAuthTokenType::PermanentLogin);
		$this->cookies->set(CookieName::PermanentLogin, $value, $this->permanentLoginInterval, $this->authCookiesPath, sameSite: 'Strict');
	}


	/**
	 * Delete all permanent login tokens and delete the cookie in the browser.
	 */
	public function clearPermanentLogin(User $user): void
	{
		$this->database->query('DELETE FROM auth_tokens WHERE key_user = ? AND type = ?', $this->getUserId($user), UserAuthTokenType::PermanentLogin->value);
		$this->cookies->delete(CookieName::PermanentLogin, $this->authCookiesPath);
	}


	/**
	 * Regenerate permanent login token.
	 *
	 * @throws Exception
	 */
	public function regeneratePermanentLogin(User $user): void
	{
		$userId = $this->getUserId($user); // Fail before starting a transaction, if you're going to fail
		$this->database->beginTransaction();
		try {
			$this->database->query('DELETE FROM auth_tokens WHERE key_user = ? AND type = ?', $userId, UserAuthTokenType::PermanentLogin->value);
			$this->storePermanentLogin($user);
			$this->database->commit();
		} catch (Exception $e) {
			$this->database->rollBack();
			throw $e;
		}
	}


	/**
	 * Verify and return permanent token, if present, and valid.
	 */
	public function verifyPermanentLogin(): ?UserAuthToken
	{
		$cookie = $this->cookies->getString(CookieName::PermanentLogin) ?? '';
		return $this->verifyToken($cookie, DateTime::from("-{$this->permanentLoginInterval}"), UserAuthTokenType::PermanentLogin);
	}


	/**
	 * Verify and return any token, if present, and valid.
	 */
	private function verifyToken(string $value, DateTimeInterface $validity, UserAuthTokenType $type): ?UserAuthToken
	{
		$values = explode(self::AUTH_SELECTOR_TOKEN_SEPARATOR, $value);
		if (count($values) !== 2) {
			return null;
		}
		$row = $this->database->fetch(
			'SELECT
				at.id_auth_token AS id,
				at.token,
				u.id_user AS userId,
				u.username
			FROM
				auth_tokens at
				JOIN ?name u ON u.id_user = at.key_user
			WHERE
				at.selector = ?
				AND at.created > ?
				AND type = ?',
			$this->usersTableName,
			$values[0],
			$validity,
			$type->value,
		);
		if ($row === null) {
			return null;
		}
		assert(is_int($row->id));
		assert(is_string($row->token));
		assert(is_int($row->userId));
		assert(is_string($row->username));

		$authToken = new UserAuthToken($row->id, $row->token, $row->userId, $row->username);
		return hash_equals($authToken->getToken(), $this->hashToken($values[1])) ? $authToken : null;
	}


	public function getPermanentLoginCookieLifetime(): string
	{
		return $this->permanentLoginInterval;
	}


	public function isPasskeyResetEnabled(): bool
	{
		return $this->resetEnabled;
	}


	/**
	 * @throws PasskeyResetDisabledException
	 * @throws Exception
	 */
	public function createPasskeyResetToken(int $userId): string
	{
		if (!$this->resetEnabled) {
			throw new PasskeyResetDisabledException();
		}
		$this->database->beginTransaction();
		try {
			$this->database->query('DELETE FROM auth_tokens WHERE key_user = ? AND type = ?', $userId, UserAuthTokenType::PasskeyReset->value);
			$token = $this->insertToken($userId, UserAuthTokenType::PasskeyReset);
			$this->database->commit();
		} catch (Exception $e) {
			$this->database->rollBack();
			throw $e;
		}
		return $token;
	}


	/**
	 * @throws PasskeyResetDisabledException
	 */
	public function verifyPasskeyResetToken(string $value): ?UserAuthToken
	{
		if (!$this->resetEnabled) {
			throw new PasskeyResetDisabledException();
		}
		return $this->verifyToken($value, DateTime::from('-' . self::RESET_TOKEN_EXPIRY), UserAuthTokenType::PasskeyReset);
	}


	public function deletePasskeyResetToken(int $tokenId): void
	{
		$this->database->query('DELETE FROM auth_tokens WHERE id_auth_token = ? AND type = ?', $tokenId, UserAuthTokenType::PasskeyReset->value);
	}

}
