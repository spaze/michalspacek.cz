<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Presentation\Api\Company;

use MichalSpacekCz\Api\Endpoint\EndpointAllowsPublicAccess;
use MichalSpacekCz\CompanyInfo\CompanyInfo;
use MichalSpacekCz\Http\FetchMetadata\ResourceIsolationPolicyCrossSite;
use MichalSpacekCz\Presentation\Api\BasePresenter;
use Nette\Application\BadRequestException;

#[EndpointAllowsPublicAccess]
final class CompanyPresenter extends BasePresenter
{

	public function __construct(
		private readonly CompanyInfo $companyInfo,
	) {
		parent::__construct();
	}


	#[ResourceIsolationPolicyCrossSite]
	public function actionDefault(?string $country, ?string $companyId): void
	{
		if ($country === null || $companyId === null) {
			throw new BadRequestException('No country or companyId specified');
		}
		$this->sendJson($this->companyInfo->getDetails($country, $companyId));
	}

}
