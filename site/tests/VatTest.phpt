<?php
declare(strict_types = 1);

namespace MichalSpacekCz;

use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/bootstrap.php';

/**
 * @testCase MichalSpacekCz\VatTest
 */
class VatTest extends TestCase
{

	/** @var Vat */
	private $vat;


	public function __construct()
	{
		$this->vat = new Vat();
	}


	public function testSetGetRate(): void
	{
		$this->vat->setRate(1.23);
		Assert::same(1.23, $this->vat->getRate());
	}


	public function testAddVat(): void
	{
		$this->vat->setRate(0.21);
		Assert::same(12088, $this->vat->addVat(9990));
	}

}

(new VatTest())->run();
