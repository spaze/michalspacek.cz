<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Test\Training;

use DateTimeInterface;
use MichalSpacekCz\ShouldNotHappenException;
use MichalSpacekCz\Training\Files\TrainingFilesStorage;
use Override;

class TrainingFilesNullStorage extends TrainingFilesStorage
{

	private string $filesDir = '';


	public function setFilesDir(string $filesDir): void
	{
		if (!str_ends_with($filesDir, '/')) {
			throw new ShouldNotHappenException("The directory {$filesDir} does not end with /");
		}
		$this->filesDir = $filesDir;
	}


	#[Override]
	public function getFilesDir(DateTimeInterface $date): string
	{
		return $this->filesDir;
	}

}
