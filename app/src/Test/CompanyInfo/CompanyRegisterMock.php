<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Test\CompanyInfo;

use MichalSpacekCz\CompanyInfo\CompanyInfoDetails;
use MichalSpacekCz\CompanyInfo\CompanyRegister;
use MichalSpacekCz\Test\WillThrow;
use Override;

final class CompanyRegisterMock implements CompanyRegister
{

	use WillThrow;


	/** @var list<string> */
	private array $companyIds = [];


	public function __construct(
		private readonly string $country,
		private readonly CompanyInfoDetails $details,
	) {
	}


	#[Override]
	public function getCountry(): string
	{
		return $this->country;
	}


	#[Override]
	public function getDetails(string $companyId): CompanyInfoDetails
	{
		$this->companyIds[] = $companyId;
		$this->maybeThrow();
		return $this->details;
	}


	/**
	 * @return list<string>
	 */
	public function getCompanyIds(): array
	{
		return $this->companyIds;
	}


	public function reset(): void
	{
		$this->companyIds = [];
		$this->wontThrow();
	}

}
