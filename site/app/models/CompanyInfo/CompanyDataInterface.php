<?php
declare(strict_types = 1);

namespace MichalSpacekCz\CompanyInfo;

interface CompanyDataInterface
{

	public function getData(string $companyId): Data;

}
