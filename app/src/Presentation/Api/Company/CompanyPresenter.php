<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Presentation\Api\Company;

use MichalSpacekCz\CompanyInfo\CompanyInfo;
use MichalSpacekCz\Http\FetchMetadata\ResourceIsolationPolicyCrossSite;
use MichalSpacekCz\Http\SecurityHeaders;
use MichalSpacekCz\Presentation\Api\BasePresenter;
use Nette\Application\BadRequestException;

final class CompanyPresenter extends BasePresenter
{

	public function __construct(
		private readonly CompanyInfo $companyInfo,
		private readonly SecurityHeaders $securityHeaders,
	) {
		parent::__construct();
	}


	#[ResourceIsolationPolicyCrossSite]
	public function actionDefault(?string $country, ?string $companyId): void
	{
		if ($country === null || $companyId === null) {
			throw new BadRequestException('No country or companyId specified');
		}

		$this->securityHeaders->accessControlAllowOrigin('Www:Homepage:');
		$this->sendJson($this->companyInfo->getDetails($country, $companyId));
	}

}
