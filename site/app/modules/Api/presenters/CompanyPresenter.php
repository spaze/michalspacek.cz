<?php
declare(strict_types = 1);

namespace App\ApiModule\Presenters;

/**
 * Company presenter.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
class CompanyPresenter extends \App\WwwModule\Presenters\BasePresenter
{

	/** @var \MichalSpacekCz\CompanyInfo\Info */
	protected $companyInfo;

	/** @var \MichalSpacekCz\SecurityHeaders */
	protected $securityHeaders;


	/**
	 * @param \MichalSpacekCz\CompanyInfo\Info $companyInfo
	 * @param \MichalSpacekCz\SecurityHeaders $securityHeaders
	 */
	public function __construct(\MichalSpacekCz\CompanyInfo\Info $companyInfo, \MichalSpacekCz\SecurityHeaders $securityHeaders)
	{
		$this->companyInfo = $companyInfo;
		$this->securityHeaders = $securityHeaders;
		parent::__construct();
	}


	/**
	 * @param string|null $country
	 * @param string|null $companyId
	 * @throws \Nette\Application\BadRequestException
	 * @throws \Nette\Application\AbortException
	 */
	public function actionDefault(?string $country, ?string $companyId): void
	{
		if ($country === null || $companyId === null) {
			throw new \Nette\Application\BadRequestException('No country or companyId specified', \Nette\Http\IResponse::S404_NOT_FOUND);
		}

		$this->securityHeaders->accessControlAllowOrigin('https', \MichalSpacekCz\Application\RouterFactory::HOST_WWW);

		try {
			$info = $this->companyInfo->getData($country, $companyId);
		} catch (\RuntimeException $e) {
			$info = new \MichalSpacekCz\CompanyInfo\Data();
			$info->status = \MichalSpacekCz\CompanyInfo\Info::STATUS_ERROR;
			$info->statusMessage = $e->getMessage();
		}
		$data = array(
			'status' => $info->status,
			'statusMessage' => $info->statusMessage,
			'companyId' => $info->companyId,
			'companyTaxId' => $info->companyTaxId,
			'company' => $info->company,
			'street' => $info->streetFull,
			'city' => $info->city,
			'zip' => $info->zip,
			'country' => $info->country,
		);

		$this->sendJson(array_filter($data));
	}

}
