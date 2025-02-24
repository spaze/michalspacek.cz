<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Training;

use MichalSpacekCz\ShouldNotHappenException;
use NumberFormatter;

readonly class Price
{

	public function __construct(
		private ?float $price,
		private ?int $discount,
		private ?float $vatRate,
		private ?float $priceVat = null,
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
		if ($this->priceVat !== null) {
			return $this->priceVat;
		}

		return $this->price !== null ? $this->price * (1.0 + $this->vatRate) : null;
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

		$currency = 'CZK';
		$formatted = $formatter->formatCurrency($price, $currency);
		if (!is_string($formatted)) {
			throw new ShouldNotHappenException(sprintf("Formatting '%s' %s with %s should not fail", $price, $currency, $formatter->getAttribute(NumberFormatter::FRACTION_DIGITS)));
		}
		return $formatted;
	}

}
