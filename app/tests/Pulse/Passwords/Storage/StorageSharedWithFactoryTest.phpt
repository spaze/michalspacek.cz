<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\Pulse\Passwords\Storage;

use MichalSpacekCz\Test\TestCaseRunner;
use MichalSpacekCz\Utils\Exceptions\JsonItemNotStructureException;
use MichalSpacekCz\Utils\Exceptions\JsonItemsNotArrayException;
use Nette\Utils\JsonException;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../../../bootstrap.php';

/** @testCase */
final class StorageSharedWithFactoryTest extends TestCase
{

	public function __construct(
		private readonly StorageSharedWithFactory $sharedWithFactory,
	) {
	}


	public function testGet(): void
	{
		$sharedWith = $this->sharedWithFactory->get('[{"url": "https://a.example/", "alias": "a"}, {"url": "https://b.example/", "alias": "b"}]');
		Assert::count(2, $sharedWith);
		Assert::same('https://a.example/', $sharedWith[0]->getUrl());
		Assert::same('a', $sharedWith[0]->getAlias());
		Assert::same('https://b.example/', $sharedWith[1]->getUrl());
		Assert::same('b', $sharedWith[1]->getAlias());
	}


	public function testGetNotJson(): void
	{
		Assert::exception(function (): void {
			$this->sharedWithFactory->get('[{"url":');
		}, JsonException::class);
	}


	public function testGetNotList(): void
	{
		Assert::exception(function (): void {
			$this->sharedWithFactory->get('"https://a.example/"');
		}, JsonItemsNotArrayException::class);
	}


	public function testGetItemWithoutAlias(): void
	{
		Assert::exception(function (): void {
			$this->sharedWithFactory->get('[{"url": "https://a.example/"}]');
		}, JsonItemNotStructureException::class);
	}

}

TestCaseRunner::run(StorageSharedWithFactoryTest::class);
