<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\Training\Files;

use DateTimeImmutable;
use MichalSpacekCz\Test\TestCaseRunner;
use SplFileInfo;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../../bootstrap.php';

/** @testCase */
class TrainingFilesCollectionTest extends TestCase
{

	private TrainingFilesCollection $collection;


	public function __construct()
	{
		$this->collection = new TrainingFilesCollection();
		$this->collection->add(new TrainingFile(123, 'foo', new SplFileInfo(''), new DateTimeImmutable('2022-03-11 00:00:00')));
		$this->collection->add(new TrainingFile(456, 'bar', new SplFileInfo(''), new DateTimeImmutable('2022-03-26 16:00:00')));
		$this->collection->add(new TrainingFile(789, 'baz', new SplFileInfo(''), new DateTimeImmutable('2022-03-26 15:00:00')));
	}


	public function testGetIterator(): void
	{
		$ids = [];
		foreach ($this->collection as $file) {
			Assert::type(TrainingFile::class, $file);
			$ids[] = $file->getId();
		}
		Assert::same([123, 456, 789], $ids);
	}


	public function testCount(): void
	{
		Assert::count(3, $this->collection);
	}


	public function testGetNewestFile(): void
	{
		Assert::same(456, $this->collection->getNewestFile()?->getId());
	}


	public function testGetNewestFileButNoFiles(): void
	{
		$this->collection = new TrainingFilesCollection();
		Assert::null($this->collection->getNewestFile());
	}

}

TestCaseRunner::run(TrainingFilesCollectionTest::class);
