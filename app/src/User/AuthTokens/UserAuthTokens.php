<?php
declare(strict_types = 1);

namespace MichalSpacekCz\User\AuthTokens;

use DateTimeImmutable;
use DateTimeInterface;
use Exception;
use Nette\Database\Explorer;
use Nette\Database\UniqueConstraintViolationException;
use Nette\Utils\Random;

final readonly class UserAuthTokens
{

	private const string AUTH_SELECTOR_TOKEN_SEPARATOR = ':';


	public function __construct(
		private Explorer $database,
		private string $usersTableName,
	) {
	}


	/**
	 * Insert authentication token into database.
	 *
	 * Selector and token are regenerated if selector already exists in the table.
	 *
	 * @return non-empty-string Concatenation of selector, separator, token
	 * @throws Exception
	 */
	private function insert(int $userId, UserAuthTokenType $type): string
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
					'created' => new DateTimeImmutable(),
					'type' => $type->value,
				],
			);
		} catch (UniqueConstraintViolationException) {
			return $this->insert($userId, $type);
		}
		return $selector . self::AUTH_SELECTOR_TOKEN_SEPARATOR . $token;
	}


	/**
	 * Delete all tokens of this type for the user, then insert a new one. Atomic.
	 *
	 * @return non-empty-string
	 * @throws Exception
	 */
	public function replaceForUser(int $userId, UserAuthTokenType $type): string
	{
		$this->database->beginTransaction();
		try {
			$this->deleteAllForUser($userId, $type);
			$value = $this->insert($userId, $type);
			$this->database->commit();
			return $value;
		} catch (Exception $e) {
			$this->database->rollBack();
			throw $e;
		}
	}


	/**
	 * Verify and return a token of the given type, if present and valid.
	 */
	public function verify(string $value, DateTimeInterface $validity, UserAuthTokenType $type): ?UserAuthToken
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


	public function deleteById(int $tokenId, UserAuthTokenType $type, int $userId): int
	{
		return $this->database->query('DELETE FROM auth_tokens WHERE id_auth_token = ? AND type = ? AND key_user = ?', $tokenId, $type->value, $userId)->getRowCount() ?? 0;
	}


	public function deleteAllForUser(int $userId, UserAuthTokenType $type): void
	{
		$this->database->query('DELETE FROM auth_tokens WHERE key_user = ? AND type = ?', $userId, $type->value);
	}


	/**
	 * Delete every token of the user, whatever its type. For account recovery (passkey reset), where
	 * the point is to leave no token-based way into the account behind, including types added later.
	 */
	public function deleteAllTypesForUser(int $userId): void
	{
		$this->database->query('DELETE FROM auth_tokens WHERE key_user = ?', $userId);
	}


	public function deleteExpiredByType(UserAuthTokenType $type, DateTimeImmutable $before): int
	{
		return $this->database->query('DELETE FROM auth_tokens WHERE type = ? AND created <= ?', $type->value, $before)->getRowCount() ?? 0;
	}


	/**
	 * @return non-empty-string SHA-512 hash of the token
	 */
	private function hashToken(string $token): string
	{
		return hash('sha512', $token);
	}

}
