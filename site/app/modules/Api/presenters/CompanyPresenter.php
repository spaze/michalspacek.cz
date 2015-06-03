<?php
namespace ApiModule;

/**
 * Company presenter.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
class CompanyPresenter extends \BasePresenter
{

	/** @var \MichalSpacekCz\CompanyInfo\Info */
	protected $companyInfo;

	/**
	 * @param \MichalSpacekCz\CompanyInfo\Info $companyInfo
	 */
	public function __construct(\MichalSpacekCz\CompanyInfo\Info $companyInfo)
	{
		$this->companyInfo = $companyInfo;
	}


	/**
	 * @param string $country
	 * @param string $companyId
	 */
	public function actionDefault($country, $companyId)
	{
		if (!$this->isAjax()) {
			throw new \Nette\Application\BadRequestException('Not an AJAX call');
		}

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
