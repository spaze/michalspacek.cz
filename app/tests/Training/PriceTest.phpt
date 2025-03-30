<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Training;

use MichalSpacekCz\Test\TestCaseRunner;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../bootstrap.php';

/** @testCase */
final class PriceTest extends TestCase
{

	public function testGetPriceVat(): void
	{
		$price = new Price(null, 50, 1.23);
		Assert::null($price->getPriceVat());

		$price = new Price(9990, 50, null);
		Assert::null($price->getPriceVat());

		$price = new Price(9990, null, 1.23);
		Assert::same(22277.7, $price->getPriceVat());

		$priceVat = 12345.567;
		$price = new Price(9990, null, 1.23, $priceVat);
		Assert::same($priceVat, $price->getPriceVat());
	}

}

TestCaseRunner::run(PriceTest::class);
