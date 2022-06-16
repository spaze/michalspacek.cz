<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Api\Presenters;

use MichalSpacekCz\CompanyInfo\Data;
use MichalSpacekCz\CompanyInfo\Info;
use MichalSpacekCz\Http\SecurityHeaders;
use MichalSpacekCz\Www\Presenters\BasePresenter;
use Nette\Application\AbortException;
use Nette\Application\BadRequestException;
use Nette\Http\IResponse;
use RuntimeException;

class CompanyPresenter extends BasePresenter
{

	public function __construct(
		private readonly Info $companyInfo,
		private readonly SecurityHeaders $securityHeaders,
	) {
		parent::__construct();
	}


	/**
	 * @param string|null $country
	 * @param string|null $companyId
	 * @throws BadRequestException
	 * @throws AbortException
	 */
	public function actionDefault(?string $country, ?string $companyId): void
	{
		if ($country === null || $companyId === null) {
			throw new BadRequestException('No country or companyId specified');
		}

		$this->securityHeaders->accessControlAllowOrigin('Www:Homepage:');

		try {
			$info = $this->companyInfo->getData($country, $companyId);
		} catch (RuntimeException $e) {
			$info = new Data();
			$info->status = IResponse::S500_INTERNAL_SERVER_ERROR;
			$info->statusMessage = $e->getMessage();
		}
		$data = array(
			'status' => $info->status,
			'statusMessage' => $info->statusMessage,
			'companyId' => $info->companyId,
			'companyTaxId' => $info->companyTaxId,
			'company' => $info->company,
			'street' => $info->streetFull ?? '',
			'city' => $info->city ?? '',
			'zip' => $info->zip ?? '',
			'country' => $info->country ?? '',
		);

		$this->sendJson(array_filter($data));
	}

}
