<?php
declare(strict_types = 1);

namespace MichalSpacekCz\CompanyInfo;

class CompanyDetails
{

	public int $status;
	public string $statusMessage;
	public string $companyId;
	public string $companyTaxId;
	public string $company;
	public ?string $streetAndNumber;
	public ?string $city;
	public ?string $zip;
	public ?string $country;

}
