<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Training;

use MichalSpacekCz\Test\TestCaseRunner;
use MichalSpacekCz\Training\ApplicationStatuses\TrainingApplicationStatus;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../bootstrap.php';

/** @testCase */
final class PricesTest extends TestCase
{

	public function __construct(
		private readonly Prices $prices,
	) {
	}


	public function testResolvePriceDiscountVatNoPriceTentativeTraining(): void
	{
		$price = $this->prices->resolvePriceDiscountVat($this->prices->resolvePriceVat(9990), 42, TrainingApplicationStatus::Tentative, 'FooStudentBar');
		$this->assertNoPrice($price);
	}


	public function testResolvePriceDiscountVatNoPriceNonPublicTraining(): void
	{
		$price = $this->prices->resolvePriceDiscountVat($this->prices->resolvePriceVat(9990), 42, TrainingApplicationStatus::NonPublicTraining, 'FooStudentBar');
		$this->assertNoPrice($price);
	}


	public function testResolvePriceNoDiscountVat(): void
	{
		$price = $this->prices->resolvePriceDiscountVat($this->prices->resolvePriceVat(9990), 50, TrainingApplicationStatus::SignedUp, 'foo');
		Assert::same(9990.0, $price->getPrice());
		Assert::same(12087.9, $price->getPriceVat());
		Assert::same('9 990 Kč', $price->getPriceWithCurrency());
		Assert::same('12 087,90 Kč', $price->getPriceVatWithCurrency());
	}


	public function testResolveNoPriceDiscountVat(): void
	{
		$price = $this->prices->resolvePriceDiscountVat(null, 50, TrainingApplicationStatus::SignedUp, 'yo students');
		$this->assertNoPrice($price);

		$price = $this->prices->resolvePriceDiscountVat(new Price(null, null, null), 50, TrainingApplicationStatus::SignedUp, 'yo students');
		$this->assertNoPrice($price);
	}


	public function testResolvePriceStudentDiscountVat(): void
	{
		$price = $this->prices->resolvePriceDiscountVat($this->prices->resolvePriceVat(9990), 42, TrainingApplicationStatus::SignedUp, 'FooStudentBar');
		Assert::same(5794.0, $price->getPrice());
		Assert::same(7010.74, $price->getPriceVat());
		Assert::same('5 794 Kč', $price->getPriceWithCurrency());
		Assert::same('7 010,74 Kč', $price->getPriceVatWithCurrency());

		$price = $this->prices->resolvePriceDiscountVat($this->prices->resolvePriceVat(9990), 0, TrainingApplicationStatus::SignedUp, 'Foostudent');
		Assert::same(9990.0, $price->getPrice());
		Assert::same(12087.90, $price->getPriceVat());

		$price = $this->prices->resolvePriceDiscountVat($this->prices->resolvePriceVat(9990), 100, TrainingApplicationStatus::SignedUp, 'studentBar');
		Assert::same(0.0, $price->getPrice());
		Assert::same(0.0, $price->getPriceVat());
		Assert::same('0 Kč', $price->getPriceWithCurrency());
		Assert::same('0 Kč', $price->getPriceVatWithCurrency());

		$price = $this->prices->resolvePriceDiscountVat($this->prices->resolvePriceVat(9990), 101, TrainingApplicationStatus::SignedUp, 'studentBar');
		Assert::same(9990.0, $price->getPrice());
		Assert::same(12087.90, $price->getPriceVat());
	}


	public function testResolvePriceVat(): void
	{
		$price = $this->prices->resolvePriceVat(7990);
		Assert::same(7990.0, $price->getPrice());
		Assert::same(9667.9, $price->getPriceVat());
		Assert::same('7 990 Kč', $price->getPriceWithCurrency());
		Assert::same('9 667,90 Kč', $price->getPriceVatWithCurrency());
	}


	private function assertNoPrice(Price $price): void
	{
		Assert::null($price->getPrice());
		Assert::null($price->getPriceVat());
		Assert::same('', $price->getPriceWithCurrency());
		Assert::same('', $price->getPriceVatWithCurrency());
	}

}

TestCaseRunner::run(PricesTest::class);
