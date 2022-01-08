<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Training\Files;

use DateTimeInterface;
use RuntimeException;

class TrainingFilesStorage
{

	/**
	 * Files directory, does not end with a slash.
	 */
	private string $filesDir;


	public function __construct(string $filesDir)
	{
		$path = realpath($filesDir);
		if (!$path) {
			throw new RuntimeException("Can't get absolute path, maybe {$filesDir} doesn't exist?");
		}
		$this->filesDir = $path;
	}


	/**
	 * Returns a path that ends with a slash.
	 */
	public function getFilesDir(DateTimeInterface $date): string
	{
		return $this->filesDir . '/' . $date->format('Y-m-d') . '/';
	}

}
