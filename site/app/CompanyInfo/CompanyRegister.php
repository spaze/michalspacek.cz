<?php
declare(strict_types = 1);

namespace MichalSpacekCz\CompanyInfo;

use MichalSpacekCz\CompanyInfo\Exceptions\CompanyInfoException;
use MichalSpacekCz\CompanyInfo\Exceptions\CompanyNotFoundException;

interface CompanyRegister
{

	public function getCountry(): string;


	/**
	 * @throws CompanyInfoException
	 * @throws CompanyNotFoundException
	 */
	public function getDetails(string $companyId): CompanyInfoDetails;

}
