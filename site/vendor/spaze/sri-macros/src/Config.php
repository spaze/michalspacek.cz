<?php
declare(strict_types = 1);

namespace Spaze\SubresourceIntegrity;

use Spaze\SubresourceIntegrity\Exceptions\CannotGetFilePathForRemoteResourceException;
use Spaze\SubresourceIntegrity\Exceptions\HashFileException;
use Spaze\SubresourceIntegrity\Exceptions\InvalidResourceAliasException;
use Spaze\SubresourceIntegrity\Exceptions\ShouldNotHappenException;
use Spaze\SubresourceIntegrity\Exceptions\UnknownModeException;
use Spaze\SubresourceIntegrity\Resource\FileResource;
use Spaze\SubresourceIntegrity\Resource\StringResource;
use stdClass;

class Config
{

	/** @internal separator between multiple resources */
	public const BUILD_SEPARATOR = '+';

	/** @var array<string, string|array{url: string, hash: string|array<int, string>}> */
	private array $resources = [];

	/** @var array{url: string, path: string, build: string} */
	private array $localPrefix = [
		'url' => '',
		'path' => '',
		'build' => '',
	];

	private LocalMode $localMode = LocalMode::Direct;

	/** @var array<int, HashingAlgo> */
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


	public function setLocalBuildPrefix(string $prefix): void
	{
		$this->localPrefix['build'] = $prefix;
	}


	public function getLocalPathBuildPrefix(): string
	{
		return sprintf('%s/%s', rtrim($this->localPrefix['path'], '/'), trim($this->localPrefix['build'], '/'));
	}


	public function setLocalMode(LocalMode|string $localMode): void
	{
		$this->localMode = is_string($localMode) ? LocalMode::from($localMode) : $localMode;
	}


	/**
	 * Set one or more hashing algorithms.
	 *
	 * @param array<int, HashingAlgo|string> $algos
	 */
	public function setHashingAlgos(array $algos): void
	{
		$this->hashingAlgos = array_map(
			fn(HashingAlgo|string $algo) => is_string($algo) ? HashingAlgo::from($algo) : $algo,
			$algos,
		);
	}


	/**
	 * @param string|array<int, string> $resource
	 * @throws ShouldNotHappenException
	 */
	public function getUrl(string|array $resource, ?HtmlElement $targetHtmlElement = null): string
	{
		if (!is_array($resource) && $this->isRemote($resource)) {
			if (!is_array($this->resources[$resource])) {
				throw new ShouldNotHappenException();
			}
			$url = $this->resources[$resource]['url'];
		} else {
			$url = sprintf(
				'%s/%s',
				rtrim($this->localPrefix['url'], '/'),
				$this->localFile($resource, $targetHtmlElement)->url,
			);
		}
		return $url;
	}


	/**
	 * @param string|array<int, string> $resource
	 * @throws ShouldNotHappenException
	 */
	public function getHash(string|array $resource, ?HtmlElement $targetHtmlElement = null): string
	{
		if (!is_array($resource) && $this->isRemote($resource)) {
			if (!is_array($this->resources[$resource])) {
				throw new ShouldNotHappenException();
			}
			if (is_array($this->resources[$resource]['hash'])) {
				$hash = implode(' ', $this->resources[$resource]['hash']);
			} else {
				$hash = $this->resources[$resource]['hash'];
			}
		} else {
			$fileHashes = [];
			foreach ($this->hashingAlgos as $algo) {
				$filename = $this->localFile($resource, $targetHtmlElement)->filename;
				$hash = hash_file($algo->value, $filename, true);
				if (!$hash) {
					throw new HashFileException($algo, $filename);
				}
				$fileHashes[] = $algo->value . '-' . base64_encode($hash);
			}
			$hash = implode(' ', $fileHashes);
		}
		return $hash;
	}


	private function isRemote(string $resource): bool
	{
		$isArray = isset($this->resources[$resource]) && is_array($this->resources[$resource]);
		if ($isArray && $this->isCombo($resource)) {
			throw new InvalidResourceAliasException();
		}
		return $isArray;
	}


	/**
	 * @param string|array<int, string> $resource
	 * @throws ShouldNotHappenException
	 * @throws CannotGetFilePathForRemoteResourceException
	 */
	private function localFile(string|array $resource, ?HtmlElement $targetHtmlElement = null): stdClass
	{
		$resourceKey = implode(self::BUILD_SEPARATOR, (array)$resource);
		if (empty($this->localResources[$this->localMode->value][$resourceKey])) {
			switch ($this->localMode) {
				case LocalMode::Direct:
					if (is_array($resource) || $this->isCombo($resource)) {
						throw new InvalidResourceAliasException();
					}
					$data = new stdClass();
					$data->url = $this->getFilePath($resource);
					$cwd = getcwd();
					if (!$cwd) {
						throw new ShouldNotHappenException();
					}
					$data->filename = sprintf('%s/%s/%s', rtrim($cwd, '/'), trim($this->localPrefix['path'], '/'), $data->url);
					break;
				case LocalMode::Build:
					$resources = [];
					foreach ((array)$resource as $value) {
						if (!isset($this->resources[$value])) {
							$resources[] = new StringResource($value);
						} else {
							$resources[] = $this->getFileResource($value);
						}
					}
					$data = $this->fileBuilder->build($resources, $this->localPrefix['path'], $this->localPrefix['build'], $targetHtmlElement);
					break;
				default:
					throw new UnknownModeException('Unknown local file mode: ' . $this->localMode->value);
			}
			$this->localResources[$this->localMode->value][$resourceKey] = $data;
		}
		return $this->localResources[$this->localMode->value][$resourceKey];
	}


	/**
	 * Whether the resource is a combination one (e.g. foo+bar).
	 */
	private function isCombo(string $resource): bool
	{
		return strpos($resource, self::BUILD_SEPARATOR) !== false;
	}


	/**
	 * @throws CannotGetFilePathForRemoteResourceException
	 */
	private function getFilePath(string $resource): string
	{
		if (is_array($this->resources[$resource])) {
			throw new CannotGetFilePathForRemoteResourceException($resource);
		}
		return ltrim($this->resources[$resource], '/');
	}


	/**
	 * @throws CannotGetFilePathForRemoteResourceException
	 */
	public function getFileResource(string $resource): FileResource
	{
		return new FileResource(sprintf('%s/%s', rtrim($this->localPrefix['path'], '/'), $this->getFilePath($resource)));
	}

}
