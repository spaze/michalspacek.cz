<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Training;

use MichalSpacekCz\ShouldNotHappenException;
use NumberFormatter;

final readonly class Price
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
		if ($this->price === null || $this->vatRate === null) {
			return null;
		}
		return $this->price * (1.0 + $this->vatRate);
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
		// Creating one costs an order of magnitude more than using it, one per setting so none of them is changed later
		/** @var array<string, NumberFormatter> $formatters */
		static $formatters = [];
		$wholeNumber = fmod($price, 1) === (float)0;
		$key = $wholeNumber ? 'whole' : 'fraction';
		if (!isset($formatters[$key])) {
			$formatter = new NumberFormatter('cs_CZ', NumberFormatter::CURRENCY);
			if ($wholeNumber) {
				$formatter->setAttribute(NumberFormatter::FRACTION_DIGITS, 0);
			}
			$formatters[$key] = $formatter;
		}
		$formatter = $formatters[$key];

		$currency = 'CZK';
		$formatted = $formatter->formatCurrency($price, $currency);
		if (!is_string($formatted)) {
			throw new ShouldNotHappenException(sprintf("Formatting '%s' %s with %s should not fail", $price, $currency, $formatter->getAttribute(NumberFormatter::FRACTION_DIGITS)));
		}
		return $formatted;
	}

}
