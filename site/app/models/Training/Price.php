<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Training;

use NumberFormatter;

class Price
{

	/** @var integer|null */
	private $price;

	/** @var integer|null */
	private $discount;

	/** @var float|null */
	private $vatRate;


	public function __construct(?int $price, ?int $discount, ?float $vatRate)
	{
		$this->price = $price;
		$this->discount = $discount;
		$this->vatRate = $vatRate;
	}


	public function getPrice(): ?int
	{
		return $this->price;
	}


	public function getPriceAsString(): string
	{
		if ($this->price === null) {
			return '';
		}

		return $this->getNumberFormatter($this->price)->formatCurrency($this->price, 'CZK');
	}


	public function getDiscount(): ?int
	{
		return $this->discount;
	}


	public function getVatRate(): ?float
	{
		return $this->vatRate;
	}


	public function getPriceVat(): ?float
	{
		return $this->price !== null ? $this->price * (1 + $this->vatRate) : null;
	}


	public function getPriceVatAsString(): string
	{
		$priceVat = $this->getPriceVat();
		if ($priceVat === null) {
			return '';
		}

		return $this->getNumberFormatter($priceVat)->formatCurrency($priceVat, 'CZK');
	}


	private function getNumberFormatter(float $price): NumberFormatter
	{
		$formatter = new NumberFormatter('cs_CZ', NumberFormatter::CURRENCY);
		if (fmod($price, 1) === (float)0) {
			$formatter->setAttribute(NumberFormatter::FRACTION_DIGITS, 0);
		}
		return $formatter;
	}

}
