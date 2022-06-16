<?php
declare(strict_types = 1);

namespace MichalSpacekCz\CompanyInfo;

class Data
{

	public int $status;
	public string $statusMessage;
	public string $companyId;
	public string $companyTaxId;
	public string $company;
	public ?string $street;
	public ?string $houseNumber;
	public ?string $streetNumber;
	public ?string $streetFull;
	public ?string $city;
	public ?string $zip;
	public ?string $country;

}
