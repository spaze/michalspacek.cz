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

	/** @var Ares */
	private $ares;

	/** @var RegisterUz */
	private $registerUz;


	/**
	 * @param Ares $ares
	 */
	public function __construct(
		Ares $ares,
		RegisterUz $registerUz
	)
	{
		$this->ares = $ares;
		$this->registerUz = $registerUz;
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
			case 'sk':
				$data = $this->registerUz->getData($companyId);
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
