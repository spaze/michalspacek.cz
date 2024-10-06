<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Training;

use MichalSpacekCz\Test\TestCaseRunner;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../bootstrap.php';

/** @testCase */
class PriceTest extends TestCase
{

	public function testGetPriceVat(): void
	{
		$price = new Price(9990, null, 1.23);
		Assert::same(22277.7, $price->getPriceVat());

		$priceVat = 12345.567;
		$price = new Price(9990, null, 1.23, $priceVat);
		Assert::same($priceVat, $price->getPriceVat());
	}

}

TestCaseRunner::run(PriceTest::class);
