<?php
declare(strict_types = 1);

namespace MichalSpacekCz\User;

use MichalSpacekCz\Database\TypedDatabase;
use MichalSpacekCz\User\Exceptions\IdentityIdNotIntException;
use Nette\Http\IRequest;
use Nette\Security\SimpleIdentity;
use Nette\Security\User;

final readonly class Manager
{

	public function __construct(
		private TypedDatabase $typedDatabase,
		private IRequest $httpRequest,
		private string $usersTableName,
	) {
	}


	public function getIdentity(int $id, string $username): SimpleIdentity
	{
		return new SimpleIdentity($id, [], ['username' => $username]);
	}


	public function getUserIdByUsername(string $username): ?int
	{
		return $this->typedDatabase->fetchFieldIntNullable('SELECT id_user FROM ?name WHERE username = ?', $this->usersTableName, $username);
	}


	/**
	 * @throws IdentityIdNotIntException
	 */
	public function getUserId(User $user): int
	{
		$userId = $user->getId();
		if (!is_int($userId)) {
			throw new IdentityIdNotIntException(get_debug_type($userId));
		}
		return $userId;
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

}
