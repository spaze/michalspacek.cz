<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Pulse;

class Company
{

	private int $id;

	private string $companyName;

	private ?string $tradeName;

	private string $companyAlias;

	private string $sortName;


	public function __construct(int $id, string $companyName, ?string $tradeName, string $companyAlias, string $sortName)
	{
		$this->id = $id;
		$this->companyName = $companyName;
		$this->tradeName = $tradeName;
		$this->companyAlias = $companyAlias;
		$this->sortName = $sortName;
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
