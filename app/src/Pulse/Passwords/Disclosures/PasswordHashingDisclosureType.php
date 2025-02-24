<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Pulse\Passwords\Disclosures;

final readonly class PasswordHashingDisclosureType
{

	public function __construct(
		private int $id,
		private string $alias,
		private string $type,
	) {
	}


	public function getId(): int
	{
		return $this->id;
	}


	public function getAlias(): string
	{
		return $this->alias;
	}


	public function getType(): string
	{
		return $this->type;
	}

}
