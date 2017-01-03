<?php
namespace App\ApiModule\Presenters;

/**
 * Company presenter.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
class CompanyPresenter extends \App\Presenters\BasePresenter
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
	 * @param string $country
	 * @param string $companyId
	 */
	public function actionDefault($country, $companyId)
	{
		$this->securityHeaders->accessControlAllowOrigin('https', \MichalSpacekCz\Application\RouterFactory::WWW);

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
