<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Application\MappingCheck\Files;

interface ApplicationMappingCheckFile
{

	public function getFilename(): string;


	public function isPrimaryFile(): bool;


	/**
	 * @return array<string, string>
	 */
	public function getMapping(): array;

}
