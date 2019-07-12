<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Training;

use MichalSpacekCz\Vat;
use Nette\Database\Row;

class Price
{

	/** @var Vat */
	private $vat;

	/** @var integer|null */
	private $price;

	/** @var float|null */
	private $vatRate;

	/** @var integer|null */
	private $priceVat;

	/** @var integer|null */
	private $discount;


	public function __construct(Vat $vat)
	{
		$this->vat = $vat;
	}


	public function resolvePriceDiscountVat(Row $training, string $status, string $note): void
	{
		if (in_array($status, [Statuses::STATUS_NON_PUBLIC_TRAINING, Statuses::STATUS_TENTATIVE])) {
			$this->price = null;
			$this->discount = null;
		} elseif (stripos($note, 'student') === false) {
			$this->price = $training->price;
			$this->discount = null;
		} else {
			$this->price = (int)($training->price * (100 - $training->studentDiscount) / 100);
			$this->discount = $training->studentDiscount;
		}

		if ($this->price === null) {
			$this->vatRate = null;
			$this->priceVat = null;
		} else {
			$this->vatRate = $this->vat->getRate();
			$this->priceVat = $this->vat->addVat($this->price);
		}
	}


	public function getPrice(): ?int
	{
		return $this->price;
	}


	public function getVatRate(): ?float
	{
		return $this->vatRate;
	}


	public function getPriceVat(): ?int
	{
		return $this->priceVat;
	}


	public function getDiscount(): ?int
	{
		return $this->discount;
	}

}
