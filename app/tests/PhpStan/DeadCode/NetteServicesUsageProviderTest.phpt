<?php
declare(strict_types = 1);

namespace MichalSpacekCz\PhpStan\DeadCode;

use MichalSpacekCz\Test\TestCaseRunner;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../../bootstrap.php';

/** @testCase */
final class NetteServicesUsageProviderTest extends TestCase
{

	public function testScalarFqcnInAnonymousEntry(): void
	{
		Assert::same(
			['Foo\Bar'],
			NetteServicesUsageProvider::findServiceClassesInNeon("services:\n\t- Foo\\Bar"),
		);
	}


	public function testScalarFqcnInNamedEntry(): void
	{
		Assert::same(
			['Foo\Bar'],
			NetteServicesUsageProvider::findServiceClassesInNeon("services:\n\tservice: Foo\\Bar"),
		);
	}


	public function testEntityWithConstructorArgs(): void
	{
		Assert::same(
			['Foo\Bar'],
			NetteServicesUsageProvider::findServiceClassesInNeon("services:\n\t- Foo\\Bar(arg1, arg2)"),
		);
	}


	public function testCreateMapping(): void
	{
		$neon = <<<'NEON'
			services:
				service:
					create: Foo\Bar
			NEON;
		Assert::same(['Foo\Bar'], NetteServicesUsageProvider::findServiceClassesInNeon($neon));
	}


	public function testCreateMappingWithConstructorArgs(): void
	{
		$neon = <<<'NEON'
			services:
				service:
					create: Foo\Bar(arg1, arg2)
			NEON;
		Assert::same(['Foo\Bar'], NetteServicesUsageProvider::findServiceClassesInNeon($neon));
	}


	public function testFactoryMapping(): void
	{
		$neon = <<<'NEON'
			services:
				service:
					factory: Foo\Bar
			NEON;
		Assert::same(['Foo\Bar'], NetteServicesUsageProvider::findServiceClassesInNeon($neon));
	}


	public function testLeadingBackslashStripped(): void
	{
		Assert::same(
			['Foo\Bar'],
			NetteServicesUsageProvider::findServiceClassesInNeon("services:\n\t- \\Foo\\Bar"),
		);
	}


	public function testSkipsServiceReference(): void
	{
		Assert::same(
			[],
			NetteServicesUsageProvider::findServiceClassesInNeon("services:\n\t- @otherService::create()"),
		);
	}


	public function testSkipsNamedServiceReference(): void
	{
		Assert::same(
			[],
			NetteServicesUsageProvider::findServiceClassesInNeon("services:\n\tservice: @otherService::method"),
		);
	}


	public function testSkipsImportedTypeMapping(): void
	{
		$neon = <<<'NEON'
			services:
				service:
					type: Foo\Bar
					imported: true
			NEON;
		Assert::same([], NetteServicesUsageProvider::findServiceClassesInNeon($neon));
	}


	public function testImplementMappingRecurses(): void
	{
		$neon = <<<'NEON'
			services:
				service:
					implement: Foo\BarFactory
			NEON;
		Assert::same(['Foo\BarFactory'], NetteServicesUsageProvider::findServiceClassesInNeon($neon));
	}


	public function testRejectsClassNameWithSpace(): void
	{
		Assert::same(
			[],
			NetteServicesUsageProvider::findServiceClassesInNeon("services:\n\t- Foo Bar"),
		);
	}


	public function testCreateWinsOverFactoryWhenBothPresent(): void
	{
		$neon = <<<'NEON'
			services:
				service:
					create: Foo\Create
					factory: Foo\Factory
			NEON;
		Assert::same(['Foo\Create'], NetteServicesUsageProvider::findServiceClassesInNeon($neon));
	}


	public function testNestedCreateChainsRecurse(): void
	{
		$neon = <<<'NEON'
			services:
				service:
					create:
						create: Foo\Bar
			NEON;
		Assert::same(['Foo\Bar'], NetteServicesUsageProvider::findServiceClassesInNeon($neon));
	}


	public function testServicesNotAnArrayReturnsEmpty(): void
	{
		Assert::same([], NetteServicesUsageProvider::findServiceClassesInNeon("services: not a list"));
	}


	public function testEntryWithOnlySetupBlockReturnsNoClass(): void
	{
		$neon = <<<'NEON'
			services:
				service:
					setup:
						- @foo::method()
			NEON;
		Assert::same([], NetteServicesUsageProvider::findServiceClassesInNeon($neon));
	}


	public function testReturnsAllRecognisedEntriesInOrder(): void
	{
		$neon = <<<'NEON'
			services:
				- Foo\Alpha
				named: Foo\Beta
				- Foo\Gamma(arg)
				bravo:
					create: Foo\Delta
				charlie:
					factory: Foo\Epsilon
				skipped: @other::method
			NEON;
		Assert::same(
			['Foo\Alpha', 'Foo\Beta', 'Foo\Gamma', 'Foo\Delta', 'Foo\Epsilon'],
			NetteServicesUsageProvider::findServiceClassesInNeon($neon),
		);
	}


	public function testReturnsEmptyForMissingServicesBlock(): void
	{
		Assert::same([], NetteServicesUsageProvider::findServiceClassesInNeon("parameters:\n\tfoo: bar"));
	}


	public function testReturnsEmptyForEmptyServicesBlock(): void
	{
		Assert::same([], NetteServicesUsageProvider::findServiceClassesInNeon("services:"));
	}

}

TestCaseRunner::run(NetteServicesUsageProviderTest::class);
