<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Pulse\Passwords\Algorithms;

final readonly class PasswordHashingAlgorithm
{

	public function __construct(
		private int $id,
		private string $name,
		private string $alias,
		private bool $salted,
		private bool $stretched,
	) {
	}


	public function getId(): int
	{
		return $this->id;
	}


	public function getName(): string
	{
		return $this->name;
	}


	public function getAlias(): string
	{
		return $this->alias;
	}


	public function isSalted(): bool
	{
		return $this->salted;
	}


	public function isStretched(): bool
	{
		return $this->stretched;
	}

}
