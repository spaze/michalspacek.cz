<?php
declare(strict_types = 1);

namespace MichalSpacekCz\CompanyInfo;

/**
 * Company Data Interface.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
interface CompanyDataInterface
{
	public function getData(string $companyId): Data;
}
