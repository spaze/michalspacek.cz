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
	private const FILES_DIR = __DIR__ . '/../../../files/trainings';


	/**
	 * Returns a path that ends with a slash.
	 */
	public function getFilesDir(DateTimeInterface $date): string
	{
		$path = realpath(self::FILES_DIR);
		if (!$path) {
			throw new RuntimeException(sprintf("Can't get real path, maybe '%s' doesn't exist? ", self::FILES_DIR));
		}
		return $path . '/' . $date->format('Y-m-d') . '/';
	}

}
