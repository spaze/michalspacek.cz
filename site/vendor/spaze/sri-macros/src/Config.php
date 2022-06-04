<?php
declare(strict_types = 1);

namespace Spaze\SubresourceIntegrity;

use Spaze\SubresourceIntegrity\Exceptions;
use Spaze\SubresourceIntegrity\Exceptions\ShouldNotHappenException;
use Spaze\SubresourceIntegrity\Resource\FileResource;
use Spaze\SubresourceIntegrity\Resource\StringResource;
use stdClass;

class Config
{

	/** @internal separator between multiple resources */
	private const BUILD_SEPARATOR = '+';

	/** @var array<string, string|array{url: string, hash: string|array<int, string>}> */
	private array $resources = [];

	/** @var array{url: string, path: string, build: string} */
	private array $localPrefix = [
		'url' => '',
		'path' => '',
		'build' => '',
	];

	private LocalMode $localMode = LocalMode::Direct;

	/** @var array<int, string> */
	private array $hashingAlgos = [];

	/** @var array<string, array<string, stdClass>> */
	private array $localResources = [];


	public function __construct(
		private FileBuilder $fileBuilder,
	) {
	}


	/**
	 * @param array<string, string|array{url: string, hash: string|array<int, string>}> $resources
	 */
	public function setResources(array $resources): void
	{
		$this->resources = $resources;
	}


	public function setLocalPrefix(stdClass $prefix): void
	{
		foreach (array_keys($this->localPrefix) as $key) {
			if (isset($prefix->$key)) {
				$this->localPrefix[$key] = $prefix->$key;
			}
		}
	}


	public function setLocalMode(LocalMode|string $localMode): void
	{
		$this->localMode = is_string($localMode) ? LocalMode::from($localMode) : $localMode;
	}


	/**
	 * Set one or more hashing algorithms.
	 *
	 * @param string[] $algos
	 */
	public function setHashingAlgos(array $algos): void
	{
		$this->hashingAlgos = $algos;
	}


	/**
	 * @throws ShouldNotHappenException
	 */
	public function getUrl(string $resource, ?string $extension = null): string
	{
		if ($this->isRemote($resource)) {
			if (!is_array($this->resources[$resource])) {
				throw new ShouldNotHappenException();
			}
			$url = $this->resources[$resource]['url'];
		} else {
			$url = sprintf(
				'%s/%s',
				rtrim($this->localPrefix['url'], '/'),
				$this->localFile($resource, $extension)->url,
			);
		}
		return $url;
	}


	/**
	 * @throws ShouldNotHappenException
	 */
	public function getHash(string $resource, ?string $extension = null): string
	{
		if ($this->isRemote($resource)) {
			if (!is_array($this->resources[$resource])) {
				throw new Exceptions\ShouldNotHappenException();
			}
			if (is_array($this->resources[$resource]['hash'])) {
				$hash = implode(' ', $this->resources[$resource]['hash']);
			} else {
				$hash = $this->resources[$resource]['hash'];
			}
		} else {
			$fileHashes = [];
			foreach ($this->hashingAlgos as $algo) {
				if (!in_array($algo, ['sha256', 'sha384', 'sha512'])) {
					throw new Exceptions\UnsupportedHashAlgorithmException();
				}
				$filename = $this->localFile($resource, $extension)->filename;
				$hash = hash_file($algo, $filename, true);
				if (!$hash) {
					throw new Exceptions\HashFileException($algo, $filename);
				}
				$fileHashes[] = $algo . '-' . base64_encode($hash);
			}
			$hash = implode(' ', $fileHashes);
		}
		return $hash;
	}


	private function isRemote(string $resource): bool
	{
		$isArray = isset($this->resources[$resource]) && is_array($this->resources[$resource]);
		if ($isArray && $this->isCombo($resource)) {
			throw new Exceptions\InvalidResourceAliasException();
		}
		return $isArray;
	}


	/**
	 * @throws ShouldNotHappenException
	 */
	private function localFile(string $resource, ?string $extension = null): stdClass
	{
		if (empty($this->localResources[$this->localMode->value][$resource])) {
			switch ($this->localMode) {
				case LocalMode::Direct:
					if ($this->isCombo($resource)) {
						throw new Exceptions\InvalidResourceAliasException();
					}
					$data = new stdClass();
					$data->url = $this->getFilePath($resource);
					$cwd = getcwd();
					if (!$cwd) {
						throw new Exceptions\ShouldNotHappenException();
					}
					$data->filename = sprintf('%s/%s/%s', rtrim($cwd, '/'), trim($this->localPrefix['path'], '/'), $data->url);
					break;
				case LocalMode::Build:
					$resources = [];
					foreach (explode(self::BUILD_SEPARATOR, $resource) as $value) {
						if (preg_match('/^[\'"](.*)[\'"]$/', $value, $matches)) {
							$resources[] = new StringResource($matches[1]);
						} else {
							$resources[] = new FileResource(sprintf('%s/%s', rtrim($this->localPrefix['path'], '/'), $this->getFilePath($value)));
						}
					}
					$data = $this->fileBuilder->build($resources, $this->localPrefix['path'], $this->localPrefix['build'], $extension);
					break;
				default:
					throw new Exceptions\UnknownModeException('Unknown local file mode: ' . $this->localMode->value);
			}
			$this->localResources[$this->localMode->value][$resource] = $data;
		}
		return $this->localResources[$this->localMode->value][$resource];
	}


	/**
	 * Whether the resource is a combination one (e.g. foo+bar).
	 *
	 * @param string $resource
	 * @return bool
	 */
	private function isCombo(string $resource): bool
	{
		return (strpos($resource, self::BUILD_SEPARATOR) !== false);
	}


	/**
	 * @throws ShouldNotHappenException
	 */
	private function getFilePath(string $resource): string
	{
		if (is_array($this->resources[$resource])) {
			throw new Exceptions\ShouldNotHappenException();
		}
		return ltrim($this->resources[$resource], '/');
	}

}
