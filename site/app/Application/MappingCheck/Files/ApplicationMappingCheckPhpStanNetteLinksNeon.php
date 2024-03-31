<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Application\MappingCheck\Files;

use MichalSpacekCz\Application\MappingCheck\Exceptions\ApplicationMappingFileNotFoundException;
use MichalSpacekCz\Application\MappingCheck\Exceptions\ApplicationMappingInvalidConfigException;
use MichalSpacekCz\ShouldNotHappenException;
use Nette\Neon\Exception;
use Nette\Neon\Neon;
use Override;

readonly class ApplicationMappingCheckPhpStanNetteLinksNeon implements ApplicationMappingCheckFile
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
		return false;
	}


	/**
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
		if (!isset($decoded['parameters'])) {
			throw new ApplicationMappingInvalidConfigException($this->filename, "Missing 'parameters' key");
		}
		if (!is_array($decoded['parameters'])) {
			throw new ApplicationMappingInvalidConfigException($this->filename, "The 'parameters' key should be an array, but it's " . get_debug_type($decoded['parameters']));
		}
		if (!isset($decoded['parameters']['netteLinks'])) {
			throw new ApplicationMappingInvalidConfigException($this->filename, "Missing 'parameters.netteLinks' key");
		}
		if (!is_array($decoded['parameters']['netteLinks'])) {
			throw new ApplicationMappingInvalidConfigException($this->filename, "The 'parameters.netteLinks' key should be an array, but it's " . get_debug_type($decoded['parameters']['netteLinks']));
		}
		if (!isset($decoded['parameters']['netteLinks']['applicationMapping'])) {
			throw new ApplicationMappingInvalidConfigException($this->filename, "Missing 'parameters.netteLinks.applicationMapping' key");
		}
		if (!is_array($decoded['parameters']['netteLinks']['applicationMapping'])) {
			throw new ApplicationMappingInvalidConfigException($this->filename, "The 'parameters.netteLinks.applicationMapping' key should be an array, but it's " . get_debug_type($decoded['parameters']['netteLinks']['applicationMapping']));
		}
		$mapping = [];
		foreach ($decoded['parameters']['netteLinks']['applicationMapping'] as $key => $value) {
			if (!is_string($key) || !is_string($value)) {
				throw new ShouldNotHappenException(sprintf('Both key and value should be an array, but they are %s => %s', get_debug_type($key), get_debug_type($value)));
			}
			$mapping[$key] = $value;
		}
		return $mapping;
	}

}
