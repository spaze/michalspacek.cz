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
	 * @param \Nette\Localization\ITranslator $translator
	 * @param \MichalSpacekCz\CompanyInfo\Info $companyInfo
	 */
	public function __construct(
		\Nette\Localization\ITranslator $translator,
		\MichalSpacekCz\CompanyInfo\Info $companyInfo
	)
	{
		$this->companyInfo = $companyInfo;
		parent::__construct($translator);
	}


	/**
	 * @param string $country
	 * @param string $companyId
	 */
	public function actionDefault($country, $companyId)
	{
		// if $this->isJson
		// verify token from session added on training page
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
