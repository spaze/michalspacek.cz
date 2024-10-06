<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Application\MappingCheck;

use MichalSpacekCz\Application\MappingCheck\Exceptions\ApplicationMappingMismatchException;
use MichalSpacekCz\Application\MappingCheck\Exceptions\ApplicationMappingMultiplePrimaryFilesException;
use MichalSpacekCz\Application\MappingCheck\Exceptions\ApplicationMappingNoOtherFilesException;
use MichalSpacekCz\Application\MappingCheck\Exceptions\ApplicationMappingNoPrimaryFileException;
use MichalSpacekCz\Application\MappingCheck\Files\ApplicationMappingCheckFile;

readonly class ApplicationMappingCheck
{

	/**
	 * @param list<ApplicationMappingCheckFile> $files
	 */
	public function __construct(
		private array $files,
	) {
	}


	/**
	 * @return list<string>
	 * @throws ApplicationMappingMultiplePrimaryFilesException
	 * @throws ApplicationMappingNoPrimaryFileException
	 * @throws ApplicationMappingMismatchException
	 * @throws ApplicationMappingNoOtherFilesException
	 */
	public function checkFiles(): array
	{
		$primaryFile = null;
		$otherFilenames = $otherFiles = [];
		foreach ($this->files as $file) {
			if ($file->isPrimaryFile()) {
				if ($primaryFile !== null) {
					throw new ApplicationMappingMultiplePrimaryFilesException($primaryFile, $file);
				}
				$primaryFile = $file;
			} else {
				$otherFilenames[] = $file->getFilename();
				$otherFiles[] = $file;
			}
		}
		if ($primaryFile === null) {
			throw new ApplicationMappingNoPrimaryFileException($otherFilenames);
		}
		if ($otherFiles === []) {
			throw new ApplicationMappingNoOtherFilesException($primaryFile);
		}
		foreach ($otherFiles as $file) {
			if ($primaryFile->getMapping() !== $file->getMapping()) {
				throw new ApplicationMappingMismatchException($primaryFile, $file);
			}
		}
		return array_merge([$primaryFile->getFilename()], $otherFilenames);
	}

}
