<?php
declare(strict_types = 1);

namespace MichalSpacekCz\User;

final readonly class UserAuthToken
{

	public function __construct(
		private int $id,
		private string $token,
		private int $userId,
		private string $username,
	) {
	}


	public function getId(): int
	{
		return $this->id;
	}


	public function getToken(): string
	{
		return $this->token;
	}


	public function getUserId(): int
	{
		return $this->userId;
	}


	public function getUsername(): string
	{
		return $this->username;
	}

}
