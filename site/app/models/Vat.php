<?php
declare(strict_types = 1);

namespace MichalSpacekCz;

/**
 * VAT service.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
class Vat
{

	/** @var float */
	protected $rate;


	/**
	 * @param float $rate
	 */
	public function setRate(float $rate): void
	{
		$this->rate = $rate;
	}


	/**
	 * @return float
	 */
	public function getRate(): float
	{
		return $this->rate;
	}


	/**
	 * @param integer $price
	 * @return int
	 */
	public function addVat(int $price): int
	{
		return (int)round($price * (1 + $this->rate));
	}

}
