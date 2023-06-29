<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Training;

use MichalSpacekCz\ShouldNotHappenException;
use MichalSpacekCz\Test\Database\Database;
use Tester\Assert;
use Tester\TestCase;

$runner = require __DIR__ . '/../bootstrap.php';

/** @testCase */
class ApplicationsTest extends TestCase
{

	public function __construct(
		private readonly Applications $applications,
		private readonly Database $database,
	) {
	}


	public function testGetValidUnpaidCount(): void
	{
		$this->database->setFetchFieldResult(909);
		Assert::same(909, $this->applications->getValidUnpaidCount());

		$this->database->setFetchFieldResult('\o/');
		Assert::throws(function (): void {
			$this->applications->getValidUnpaidCount();
		}, ShouldNotHappenException::class, 'Count is a string not an integer');
	}

}

$runner->run(ApplicationsTest::class);
