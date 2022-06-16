<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Training;

use NumberFormatter;

class Price
{

	public function __construct(
		private readonly ?float $price,
		private readonly ?int $discount,
		private readonly ?float $vatRate,
		private readonly ?float $priceVat = null,
	) {
	}


	public function getPrice(): ?float
	{
		return $this->price;
	}


	public function getPriceWithCurrency(): string
	{
		if ($this->price === null) {
			return '';
		}

		return $this->formatCurrency($this->price);
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
		if ($this->priceVat) {
			return $this->priceVat;
		}

		return $this->price !== null ? $this->price * (1 + $this->vatRate) : null;
	}


	public function getPriceVatWithCurrency(): string
	{
		$priceVat = $this->getPriceVat();
		if ($priceVat === null) {
			return '';
		}

		return $this->formatCurrency($priceVat);
	}


	private function formatCurrency(float $price): string
	{
		$formatter = new NumberFormatter('cs_CZ', NumberFormatter::CURRENCY);
		if (fmod($price, 1) === (float)0) {
			$formatter->setAttribute(NumberFormatter::FRACTION_DIGITS, 0);
		}

		/** @var string $formatted */
		$formatted = $formatter->formatCurrency($price, 'CZK');
		return $formatted;
	}

}
