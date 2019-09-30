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

	/** @var Vat */
	private $vat;

	/** @var Price */
	private $price;


	public function __construct()
	{
		$this->vat = new Vat();
		$this->price = new Price($this->vat);

		$this->vat->setRate(0.21);
	}


	public function testResolvePriceDiscountVat(): void
	{
		Assert::same('', $this->price->getPriceVatAsString());
		$this->price->resolvePriceDiscountVat(9990, 50, Statuses::STATUS_SIGNED_UP, 'foo');
		Assert::same(9990, $this->price->getPrice());
		Assert::same(12088, $this->price->getPriceVat());
		Assert::same('9 990 Kč', $this->price->getPriceAsString());
		Assert::same('12 088 Kč', $this->price->getPriceVatAsString());

		$this->price->resolvePriceDiscountVat(9990, 42, Statuses::STATUS_SIGNED_UP, 'FooStudentBar');
		Assert::same(5794, $this->price->getPrice());
		Assert::same(7011, $this->price->getPriceVat());
		Assert::same('5 794 Kč', $this->price->getPriceAsString());
		Assert::same('7 011 Kč', $this->price->getPriceVatAsString());
	}


	public function testResolvePriceVat(): void
	{
		$this->price->resolvePriceVat(7990);
		Assert::same(7990, $this->price->getPrice());
		Assert::same(9668, $this->price->getPriceVat());
		Assert::same('7 990 Kč', $this->price->getPriceAsString());
		Assert::same('9 668 Kč', $this->price->getPriceVatAsString());
	}

}

(new PriceTest())->run();
