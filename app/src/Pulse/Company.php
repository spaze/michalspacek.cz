<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Pulse;

final readonly class Company
{

	public function __construct(
		private int $id,
		private string $companyName,
		private ?string $tradeName,
		private string $companyAlias,
		private string $sortName,
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
		$tradeName = $this->getTradeName();
		return $tradeName !== null ? $tradeName : $this->getCompanyName();
	}


	public function getSortName(): string
	{
		return $this->sortName;
	}

}
