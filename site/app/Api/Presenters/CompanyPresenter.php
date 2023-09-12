<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Api\Presenters;

use MichalSpacekCz\CompanyInfo\CompanyInfo;
use MichalSpacekCz\Http\SecurityHeaders;
use MichalSpacekCz\Www\Presenters\BasePresenter;
use Nette\Application\BadRequestException;

class CompanyPresenter extends BasePresenter
{

	public function __construct(
		private readonly CompanyInfo $companyInfo,
		private readonly SecurityHeaders $securityHeaders,
	) {
		parent::__construct();
	}


	public function actionDefault(?string $country, ?string $companyId): void
	{
		if ($country === null || $companyId === null) {
			throw new BadRequestException('No country or companyId specified');
		}

		$this->securityHeaders->accessControlAllowOrigin('Www:Homepage:');
		$this->sendJson($this->companyInfo->getDetails($country, $companyId));
	}

}
