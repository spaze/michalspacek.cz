<?php
declare(strict_types = 1);

namespace MichalSpacekCz;

class Vat
{

	/** @var float */
	protected $rate;


	public function setRate(float $rate): void
	{
		$this->rate = $rate;
	}


	public function getRate(): float
	{
		return $this->rate;
	}


	public function addVat(int $price): int
	{
		return (int)round($price * (1 + $this->rate));
	}

}
