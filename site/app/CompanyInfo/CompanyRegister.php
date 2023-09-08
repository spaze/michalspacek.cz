<?php
declare(strict_types = 1);

namespace MichalSpacekCz\CompanyInfo;

interface CompanyRegister
{

	public function getDetails(string $companyId): CompanyDetails;

}
