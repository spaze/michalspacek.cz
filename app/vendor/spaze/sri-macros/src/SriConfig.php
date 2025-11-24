<?php
declare(strict_types = 1);

namespace Spaze\SubresourceIntegrity;

use Spaze\SubresourceIntegrity\Exceptions\CannotGetFilePathForRemoteResourceException;
use Spaze\SubresourceIntegrity\Exceptions\HashFileException;
use Spaze\SubresourceIntegrity\Exceptions\InvalidResourceAliasException;
use Spaze\SubresourceIntegrity\Exceptions\ShouldNotHappenException;
use Spaze\SubresourceIntegrity\Resource\FileResource;
use Spaze\SubresourceIntegrity\Resource\StringResource;

class SriConfig
{

	/** @internal separator between multiple resources */
	public const string BUILD_SEPARATOR = '+';

	/** @var array<string, string|array{url: string, hash: string|array<int, string>}> */
	private array $resources = [];

	/**
	 * URL prefix for local files, will be used in templates, trailing slash removed
	 */
	private string $localUrlPrefix = '';

	/**
	 * Filesystem path prefix where the local files are located, trailing slash removed
	 */
	private string $localPathPrefix = '';

	/**
	 * Path prefix where files will be created, relative to the other prefixes, both leading and trailing slashes removed
	 */
	private string $localBuildPrefix = '';

	private LocalMode $localMode = LocalMode::Direct;

	/** @var array<int, HashingAlgo> */
	private array $hashingAlgos = [];

	/** @var array<string, array<string, LocalFile>> */
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


	public function setLocalUrlPrefix(string $prefix): void
	{
		$this->localUrlPrefix = rtrim($prefix, '/');
	}


	public function getLocalUrlPrefix(): string
	{
		return $this->localUrlPrefix;
	}


	public function setLocalPathPrefix(string $prefix): void
	{
		$this->localPathPrefix = rtrim($prefix, '/');
	}


	public function getLocalPathPrefix(): string
	{
		return $this->localPathPrefix;
	}


	public function setLocalBuildPrefix(string $prefix): void
	{
		$this->localBuildPrefix = trim($prefix, '/');
	}


	public function getLocalBuildPrefix(): string
	{
		return $this->localBuildPrefix;
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
				$this->localUrlPrefix,
				$this->localFile($resource, $targetHtmlElement)->getUrl(),
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
				$filename = $this->localFile($resource, $targetHtmlElement)->getFilename();
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
	private function localFile(string|array $resource, ?HtmlElement $targetHtmlElement = null): LocalFile
	{
		$resourceKey = implode(self::BUILD_SEPARATOR, (array)$resource);
		if (empty($this->localResources[$this->localMode->value][$resourceKey])) {
			switch ($this->localMode) {
				case LocalMode::Direct:
					if (is_array($resource) || $this->isCombo($resource)) {
						throw new InvalidResourceAliasException();
					}
					$url = $this->getFilePath($resource);
					$cwd = getcwd();
					if (!$cwd) {
						throw new ShouldNotHappenException();
					}
					$filename = sprintf('%s/%s/%s', rtrim($cwd, '/'), ltrim($this->localPathPrefix, '/'), $url);
					$localFile = new LocalFile($url, $filename);
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
					$localFile = $this->fileBuilder->build($resources, $this->localPathPrefix, $this->localBuildPrefix, $targetHtmlElement);
					break;
			}
			$this->localResources[$this->localMode->value][$resourceKey] = $localFile;
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
		return new FileResource(sprintf('%s/%s', $this->localPathPrefix, $this->getFilePath($resource)));
	}

}
