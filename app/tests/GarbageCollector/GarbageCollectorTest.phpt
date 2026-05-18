<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\GarbageCollector;

use MichalSpacekCz\Test\TestCaseRunner;
use Nette\DI\Container;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../bootstrap.php';

/** @testCase */
final class GarbageCollectorTest extends TestCase
{

	public function __construct(
		private readonly Container $container,
	) {
	}


	public function testEachImplementationDeclaresUniqueType(): void
	{
		$seen = [];
		foreach ($this->getServices() as $service) {
			$type = $service->getGarbageCollectorType();
			Assert::notContains(
				$type,
				$seen,
				sprintf('%s declares %s which is already claimed by another service', $service::class, $type->name),
			);
			$seen[] = $type;
		}
	}


	public function testGetIntervalSecondsIsSanePositive(): void
	{
		foreach ($this->getServices() as $service) {
			$interval = $service->getIntervalSeconds();
			Assert::true(
				$interval > 30,
				sprintf('%s::getIntervalSeconds() returned %d, expected a number of seconds (not minutes, hours, ...)', $service::class, $interval),
			);
		}
	}


	/**
	 * @return list<GarbageCollector>
	 */
	private function getServices(): array
	{
		$services = [];
		foreach ($this->container->findByType(GarbageCollector::class) as $name) {
			$service = $this->container->getService($name);
			assert($service instanceof GarbageCollector);
			$services[] = $service;
		}
		Assert::true(count($services) > 0, 'No GarbageCollector services found in DI container');
		return $services;
	}

}

TestCaseRunner::run(GarbageCollectorTest::class);
