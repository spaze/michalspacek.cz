<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\User\AuthTokens;

use DateTimeImmutable;
use MichalSpacekCz\Test\TestCaseRunner;
use Nette\DI\Container;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../../bootstrap.php';

/** @testCase */
final class UserAuthTokenLifetimeTest extends TestCase
{

	public function __construct(
		private readonly Container $container,
	) {
	}


	public function testGetTtlIsAPositiveInterval(): void
	{
		foreach ($this->getServices() as $service) {
			$ttl = $service->getTtl();
			$past = null;
			Assert::noError(function () use (&$past, $ttl): void {
				$past = new DateTimeImmutable('-' . $ttl);
			});
			assert($past instanceof DateTimeImmutable);
			$now = new DateTimeImmutable();
			Assert::true(
				$past < $now,
				sprintf("%s::getTtl() returned '%s', return a positive interval instead", $service::class, $ttl),
			);
		}
	}


	public function testGetTtlIsNotEmpty(): void
	{
		foreach ($this->getServices() as $service) {
			Assert::notSame('', $service->getTtl(), $service::class . '::getTtl() returned an empty string');
		}
	}


	public function testEachImplementationDeclaresUniqueType(): void
	{
		$seen = [];
		foreach ($this->getServices() as $service) {
			$type = $service->getTokenType();
			Assert::notContains(
				$type,
				$seen,
				sprintf('%s declares %s which is already claimed by another service', $service::class, $type->name),
			);
			$seen[] = $type;
		}
	}


	/**
	 * @return list<UserAuthTokenLifetime>
	 */
	private function getServices(): array
	{
		$services = [];
		foreach ($this->container->findByType(UserAuthTokenLifetime::class) as $name) {
			$service = $this->container->getService($name);
			assert($service instanceof UserAuthTokenLifetime);
			$services[] = $service;
		}
		Assert::true(count($services) > 0, 'No UserAuthTokenLifetime services found in DI container');
		return $services;
	}

}

TestCaseRunner::run(UserAuthTokenLifetimeTest::class);
