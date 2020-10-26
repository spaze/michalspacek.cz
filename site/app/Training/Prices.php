<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Training;

class Prices
{

	/** @var float|null */
	private $vatRate;


	public function setVatRate(float $vatRate): void
	{
		$this->vatRate = $vatRate;
	}


	public function resolvePriceVat(int $price): Price
	{
		return new Price($price, null, $this->vatRate);
	}


	public function resolvePriceDiscountVat(?Price $price, ?int $studentDiscount, string $status, string $note): Price
	{
		if ($price === null || in_array($status, [Statuses::STATUS_NON_PUBLIC_TRAINING, Statuses::STATUS_TENTATIVE], true)) {
			return new Price(null, null, null);
		}

		if (stripos($note, 'student') !== false && $studentDiscount !== null && $studentDiscount > 0 && $studentDiscount <= 100) {
			return new Price((int)($price->getPrice() * (100 - $studentDiscount) / 100), $studentDiscount, $price->getVatRate());
		}

		return $price;
	}

}
