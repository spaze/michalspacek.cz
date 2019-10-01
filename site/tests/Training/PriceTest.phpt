<?php
declare(strict_types = 1);

namespace MichalSpacekCz;

use MichalSpacekCz\Training\Price;
use MichalSpacekCz\Training\Statuses;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../bootstrap.php';

/**
 * @testCase MichalSpacekCz\Training\PriceTest
 */
class PriceTest extends TestCase
{

	/** @var Price */
	private $price;


	public function setUp()
	{
		$this->price = new Price();
		$this->price->setVatRate(0.21);
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
		Assert::same('', $this->price->getPriceVatAsString());
		$this->price->resolvePriceDiscountVat(9990, 50, Statuses::STATUS_SIGNED_UP, 'foo');
		Assert::same(9990, $this->price->getPrice());
		Assert::same(12087.9, $this->price->getPriceVat());
		Assert::same('9 990 Kč', $this->price->getPriceAsString());
		Assert::same('12 087,90 Kč', $this->price->getPriceVatAsString());
	}


	public function testResolvePriceStudentDiscountVat(): void
	{
		$this->price->resolvePriceDiscountVat(9990, 42, Statuses::STATUS_SIGNED_UP, 'FooStudentBar');
		Assert::same(5794, $this->price->getPrice());
		Assert::same(7010.74, $this->price->getPriceVat());
		Assert::same('5 794 Kč', $this->price->getPriceAsString());
		Assert::same('7 010,74 Kč', $this->price->getPriceVatAsString());
	}


	public function testResolvePriceVat(): void
	{
		$this->price->resolvePriceVat(7990);
		Assert::same(7990, $this->price->getPrice());
		Assert::same(9667.9, $this->price->getPriceVat());
		Assert::same('7 990 Kč', $this->price->getPriceAsString());
		Assert::same('9 667,90 Kč', $this->price->getPriceVatAsString());
	}


	private function assertNoPrice(string $status): void
	{
		$this->price->resolvePriceDiscountVat(9990, 42, $status, 'FooStudentBar');
		Assert::null($this->price->getPrice());
		Assert::null($this->price->getPriceVat());
		Assert::same('', $this->price->getPriceAsString());
		Assert::same('', $this->price->getPriceVatAsString());
	}

}

(new PriceTest())->run();
