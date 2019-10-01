<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Training;

use NumberFormatter;

class Price
{

	/** @var integer|null */
	private $price;

	/** @var float|null */
	private $vatRate;

	/** @var integer|null */
	private $priceVat;

	/** @var integer|null */
	private $discount;


	public function setVatRate(float $vatRate): void
	{
		$this->vatRate = $vatRate;
	}


	public function resolvePriceVat(int $price): void
	{
		$this->resolve($price, null, false);
	}


	public function resolvePriceDiscountVat(int $price, int $studentDiscount, string $status, string $note): void
	{
		$this->resolve(
			$price,
			stripos($note, 'student') !== false ? $studentDiscount : null,
			in_array($status, [Statuses::STATUS_NON_PUBLIC_TRAINING, Statuses::STATUS_TENTATIVE], true),
		);
	}


	private function resolve(int $price, ?int $studentDiscount, bool $noPrice): void
	{
		if ($noPrice) {
			$this->price = null;
			$this->discount = null;
		} elseif ($studentDiscount === null) {
			$this->price = $price;
			$this->discount = null;
		} else {
			$this->price = (int)($price * (100 - $studentDiscount) / 100);
			$this->discount = $studentDiscount;
		}

		if ($this->price === null) {
			$this->vatRate = null;
			$this->priceVat = null;
		} else {
			$this->priceVat = $this->price * (1 + $this->vatRate);
		}
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


	public function getVatRate(): ?float
	{
		return $this->vatRate;
	}


	public function getPriceVat(): ?float
	{
		return $this->priceVat;
	}


	public function getPriceVatAsString(): string
	{
		if ($this->priceVat === null) {
			return '';
		}

		return $this->getNumberFormatter($this->priceVat)->formatCurrency($this->priceVat, 'CZK');
	}


	public function getDiscount(): ?int
	{
		return $this->discount;
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
