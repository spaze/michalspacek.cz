<?php
declare(strict_types = 1);

namespace MichalSpacekCz\User\WebAuthn;

use DateTimeImmutable;

final readonly class RegisteredPasskey
{

	private ?int $lastUsedDaysAgo;


	public function __construct(
		private string $id,
		private string $name,
		private DateTimeImmutable $createdAt,
		private ?DateTimeImmutable $lastUsedAt,
		DateTimeImmutable $now,
		private bool $isSignedInWith = false,
	) {
		if ($lastUsedAt !== null) {
			$timezone = $lastUsedAt->getTimezone();
			$this->lastUsedDaysAgo = $lastUsedAt->setTime(0, 0)->diff($now->setTimezone($timezone)->setTime(0, 0))->days;
		} else {
			$this->lastUsedDaysAgo = null;
		}
	}


	public function getId(): string
	{
		return $this->id;
	}


	public function getName(): string
	{
		return $this->name;
	}


	public function getCreatedAt(): DateTimeImmutable
	{
		return $this->createdAt;
	}


	public function getLastUsedAt(): ?DateTimeImmutable
	{
		return $this->lastUsedAt;
	}


	public function getLastUsedDaysAgo(): ?int
	{
		return $this->lastUsedDaysAgo;
	}


	public function isSignedInWith(): bool
	{
		return $this->isSignedInWith;
	}

}
