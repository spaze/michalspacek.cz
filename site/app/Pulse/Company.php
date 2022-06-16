<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Pulse;

class Company
{

	public function __construct(
		private readonly int $id,
		private readonly string $companyName,
		private readonly ?string $tradeName,
		private readonly string $companyAlias,
		private readonly string $sortName,
	) {
	}


	public function getId(): int
	{
		return $this->id;
	}


	public function getCompanyName(): string
	{
		return $this->companyName;
	}


	public function getTradeName(): ?string
	{
		return $this->tradeName;
	}


	public function getCompanyAlias(): string
	{
		return $this->companyAlias;
	}


	public function getDisplayName(): string
	{
		return $this->getTradeName() ?: $this->getCompanyName();
	}


	public function getSortName(): string
	{
		return $this->sortName;
	}

}
