<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Application\MappingCheck\Files;

use MichalSpacekCz\Application\MappingCheck\Exceptions\ApplicationMappingFileNotFoundException;
use MichalSpacekCz\Application\MappingCheck\Exceptions\ApplicationMappingInvalidConfigException;
use MichalSpacekCz\ShouldNotHappenException;
use Nette\Neon\Exception;
use Nette\Neon\Neon;
use Override;

readonly class ApplicationMappingCheckCommonNeon implements ApplicationMappingCheckFile
{

	private string $filename;


	/**
	 * @throws ApplicationMappingFileNotFoundException
	 */
	public function __construct(string $filename)
	{
		if (!file_exists($filename)) {
			throw new ApplicationMappingFileNotFoundException($filename);
		}
		$realpath = realpath($filename);
		$this->filename = $realpath !== false ? $realpath : $filename;
	}


	#[Override]
	public function getFilename(): string
	{
		return $this->filename;
	}


	#[Override]
	public function isPrimaryFile(): bool
	{
		return true;
	}


	/**
	 * @return array<string, string>
	 * @throws ApplicationMappingInvalidConfigException
	 * @throws Exception
	 */
	#[Override]
	public function getMapping(): array
	{
		$decoded = Neon::decodeFile($this->filename);
		if (!is_array($decoded)) {
			throw new ApplicationMappingInvalidConfigException($this->filename, "Should be an array, but it's " . get_debug_type($decoded));
		}
		if (!isset($decoded['application'])) {
			throw new ApplicationMappingInvalidConfigException($this->filename, "Missing 'application' key");
		}
		if (!is_array($decoded['application'])) {
			throw new ApplicationMappingInvalidConfigException($this->filename, "The 'application' key should be an array, but it's " . get_debug_type($decoded['application']));
		}
		if (!isset($decoded['application']['mapping'])) {
			throw new ApplicationMappingInvalidConfigException($this->filename, "Missing 'application.mapping' key");
		}
		if (!is_array($decoded['application']['mapping'])) {
			throw new ApplicationMappingInvalidConfigException($this->filename, "The 'application.mapping' should be an array, but it's " . get_debug_type($decoded['application']['mapping']));
		}
		$mapping = [];
		foreach ($decoded['application']['mapping'] as $key => $value) {
			if (!is_string($key) || !is_string($value)) {
				throw new ShouldNotHappenException(sprintf('Both key and value should be an array, but they are %s => %s', get_debug_type($key), get_debug_type($value)));
			}
			$mapping[$key] = $value;
		}
		return $mapping;
	}

}
