<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Training\Files;

use ArrayIterator;
use Countable;
use IteratorAggregate;

/**
 * @implements IteratorAggregate<int, TrainingFile>
 */
class TrainingFilesCollection implements IteratorAggregate, Countable
{

	/** @var array<int, TrainingFile> */
	private array $files = [];


	public function add(TrainingFile $file): void
	{
		$this->files[] = $file;
	}


	/**
	 * @return ArrayIterator<int, TrainingFile>
	 */
	public function getIterator(): ArrayIterator
	{
		return new ArrayIterator($this->files);
	}


	public function count(): int
	{
		return count($this->files);
	}


	public function getNewestFile(): TrainingFile
	{
		$newest = null;
		foreach ($this->files as $file) {
			if ($newest === null || $file->getAdded() > $newest) {
				$newest = $file;
			}
		}
		return $newest;
	}

}
