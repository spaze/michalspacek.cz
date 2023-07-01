<?php
declare(strict_types = 1);

namespace MichalSpacekCz\CompanyInfo;

interface CompanyRegistry
{

	public function getDetails(string $companyId): CompanyDetails;

}
