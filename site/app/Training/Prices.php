<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Training;

use MichalSpacekCz\Training\ApplicationStatuses\TrainingApplicationStatus;

readonly class Prices
{

	public function __construct(
		private float $vatRate,
	) {
	}


	public function resolvePriceVat(int $price): Price
	{
		return new Price($price, null, $this->vatRate);
	}


	public function resolvePriceDiscountVat(?Price $price, ?int $studentDiscount, TrainingApplicationStatus $status, string $note): Price
	{
		$priceNotRequiredStatuses = [TrainingApplicationStatus::NonPublicTraining, TrainingApplicationStatus::Tentative];
		if ($price === null || in_array($status, $priceNotRequiredStatuses, true)) {
			return new Price(null, null, null);
		}

		if (stripos($note, 'student') !== false && $studentDiscount !== null && $studentDiscount > 0 && $studentDiscount <= 100) {
			return new Price((int)($price->getPrice() * (100 - $studentDiscount) / 100), $studentDiscount, $price->getVatRate());
		}

		return $price;
	}

}
