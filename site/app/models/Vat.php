<?php
namespace MichalSpacekCz;

/**
 * VAT service.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
class Vat
{

	/**
	 * VAT rate.
	 *
	 * @var float
	 */
	protected $rate;


	/**
	 * @param float
	 */
	public function setRate($rate)
	{
		$this->rate = $rate;
	}


	/**
	 * @return float
	 */
	public function getRate()
	{
		return $this->rate;
	}


	/**
	 * @param integer
	 */
	public function addVat($price)
	{
		return round($price * (1 + $this->rate));
	}


}
