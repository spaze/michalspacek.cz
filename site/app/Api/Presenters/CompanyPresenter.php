<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Api\Presenters;

use MichalSpacekCz\CompanyInfo\CompanyDetails;
use MichalSpacekCz\CompanyInfo\CompanyInfo;
use MichalSpacekCz\Http\SecurityHeaders;
use MichalSpacekCz\Www\Presenters\BasePresenter;
use Nette\Application\BadRequestException;
use Nette\Http\IResponse;
use RuntimeException;

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

		try {
			$info = $this->companyInfo->getData($country, $companyId);
		} catch (RuntimeException $e) {
			$info = new CompanyDetails();
			$info->status = IResponse::S500_InternalServerError;
			$info->statusMessage = $e->getMessage();
		}
		$data = [
			'status' => $info->status,
			'statusMessage' => $info->statusMessage,
			'companyId' => $info->companyId,
			'companyTaxId' => $info->companyTaxId,
			'company' => $info->company,
			'street' => $info->streetFull ?? '',
			'city' => $info->city ?? '',
			'zip' => $info->zip ?? '',
			'country' => $info->country ?? '',
		];

		$this->sendJson(array_filter($data));
	}

}
