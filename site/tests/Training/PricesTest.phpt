<?php
declare(strict_types = 1);

namespace MichalSpacekCz;

use MichalSpacekCz\Training\Prices;
use MichalSpacekCz\Training\Statuses;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../bootstrap.php';

/**
 * @testCase MichalSpacekCz\Training\PricesTest
 */
class PricesTest extends TestCase
{

	/** @var Prices */
	private $prices;


	public function setUp()
	{
		$this->prices = new Prices();
		$this->prices->setVatRate(0.21);
	}


	public function testResolvePriceDiscountVatNoPriceTentativeTraining(): void
	{
		$this->assertNoPrice(Statuses::STATUS_TENTATIVE);
	}


	public function testResolvePriceDiscountVatNoPriceNonPublicTraining(): void
	{
		$this->assertNoPrice(Statuses::STATUS_NON_PUBLIC_TRAINING);
	}


	public function testResolvePriceNoDiscountVat(): void
	{
		$price = $this->prices->resolvePriceDiscountVat(9990, 50, Statuses::STATUS_SIGNED_UP, 'foo');
		Assert::same(9990, $price->getPrice());
		Assert::same(12087.9, $price->getPriceVat());
		Assert::same('9 990 Kč', $price->getPriceWithCurrency());
		Assert::same('12 087,90 Kč', $price->getPriceVatWithCurrency());
	}


	public function testResolvePriceStudentDiscountVat(): void
	{
		$price = $this->prices->resolvePriceDiscountVat(9990, 42, Statuses::STATUS_SIGNED_UP, 'FooStudentBar');
		Assert::same(5794, $price->getPrice());
		Assert::same(7010.74, $price->getPriceVat());
		Assert::same('5 794 Kč', $price->getPriceWithCurrency());
		Assert::same('7 010,74 Kč', $price->getPriceVatWithCurrency());
	}


	public function testResolvePriceVat(): void
	{
		$price = $this->prices->resolvePriceVat(7990);
		Assert::same(7990, $price->getPrice());
		Assert::same(9667.9, $price->getPriceVat());
		Assert::same('7 990 Kč', $price->getPriceWithCurrency());
		Assert::same('9 667,90 Kč', $price->getPriceVatWithCurrency());
	}


	private function assertNoPrice(string $status): void
	{
		$price = $this->prices->resolvePriceDiscountVat(9990, 42, $status, 'FooStudentBar');
		Assert::null($price->getPrice());
		Assert::null($price->getPriceVat());
		Assert::same('', $price->getPriceWithCurrency());
		Assert::same('', $price->getPriceVatWithCurrency());
	}

}

(new PricesTest())->run();
