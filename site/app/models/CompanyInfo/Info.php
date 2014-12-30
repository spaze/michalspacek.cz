<?php
namespace MichalSpacekCz\CompanyInfo;

/**
 * Company info service.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
class Info
{

	const STATUS_FOUND = 200;

	const STATUS_NOT_FOUND = 400;

	const STATUS_ERROR = 500;


	/** @var \MichalSpacekCz\CompanyInfo\Ares */
	private $ares;

	/**
	 * @param \MichalSpacekCz\CompanyInfo\Ares $ares
	 */
	public function __construct(\MichalSpacekCz\CompanyInfo\Ares $ares)
	{
		$this->ares = $ares;
	}


	public function getData($country, $companyId)
	{
		switch ($country) {
			case 'cz':
				$data = $this->ares->getData($companyId);
				switch ($data->status) {
					case Ares::STATUS_ERROR:
						$data->status = self::STATUS_ERROR;
						break;
					case Ares::STATUS_FOUND:
						$data->status = self::STATUS_FOUND;
						break;
					case Ares::STATUS_NOT_FOUND:
						$data->status = self::STATUS_NOT_FOUND;
						break;
				}
				break;
			default:
				throw new \RuntimeException('Unsupported country');
				break;
		}
		return $data;
	}

}
