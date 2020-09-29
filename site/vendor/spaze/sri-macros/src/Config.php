<?php
declare(strict_types = 1);

namespace Spaze\SubresourceIntegrity;

use Spaze\SubresourceIntegrity\Exceptions;
use Spaze\SubresourceIntegrity\Resource\FileResource;
use Spaze\SubresourceIntegrity\Resource\ResourceInterface;
use Spaze\SubresourceIntegrity\Resource\StringResource;

/**
 * SubresourceIntegrity\Config service.
 *
 * @author Michal Špaček
 */
class Config
{

	/** @internal direct access to local files */
	public const MODE_DIRECT = 'direct';

	/** @internal build local files, new file for every new resource version */
	public const MODE_BUILD = 'build';

	/** @internal separator between multiple resources */
	private const BUILD_SEPARATOR = '+';

	/** @var FileBuilder */
	private $fileBuilder;

	/** @var array<string, string|array{url: string, hash: string|array<integer, string>}> */
	protected $resources = [];

	/** @var array{url: string, path: string, build: string} */
	protected $localPrefix = array(
		'url' => '',
		'path' => '',
		'build' => '',
	);

	/** @var string */
	protected $localMode = self::MODE_DIRECT;

	/** @var array<integer, string> */
	protected $hashingAlgos = [];

	/** @var array<string, array<string, \stdClass>> */
	protected $localResources = [];


	public function __construct(FileBuilder $fileBuilder)
	{
		$this->fileBuilder = $fileBuilder;
	}


	/**
	 * Set resources.
	 *
	 * @param array<string, string|array{url: string, hash: string|array<integer, string>}> $resources
	 */
	public function setResources(array $resources): void
	{
		$this->resources = $resources;
	}


	/**
	 * Set prefix for local resources.
	 *
	 * @param \stdClass $prefix
	 */
	public function setLocalPrefix(\stdClass $prefix): void
	{
		foreach (array_keys($this->localPrefix) as $key) {
			if (isset($prefix->$key)) {
				$this->localPrefix[$key] = $prefix->$key;
			}
		}
	}


	/**
	 * Set local mode.
	 *
	 * @param string $mode
	 */
	public function setLocalMode(string $mode): void
	{
		$this->localMode = $mode;
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
	 * Get full URL for a resource.
	 *
	 * @param string $resource
	 * @return string
	 */
	public function getUrl(string $resource, ?string $extension = null): string
	{
		if ($this->isRemote($resource)) {
			if (!is_array($this->resources[$resource])) {
				throw new Exceptions\ShouldNotHappenException();
			}
			$url = $this->resources[$resource]['url'];
		} else {
			$url = sprintf(
				'%s/%s',
				rtrim($this->localPrefix['url'], '/'),
				$this->localFile($resource, $extension)->url
			);
		}
		return $url;
	}


	/**
	 * Get SRI hash for a resource.
	 *
	 * @param string $resource
	 * @return string
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
			$fileHashes = array();
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
	 * Get local file data.
	 *
	 * @param string $resource
	 * @return \stdClass
	 */
	private function localFile(string $resource, ?string $extension = null): \stdClass
	{
		if (empty($this->localResources[$this->localMode][$resource])) {
			switch ($this->localMode) {
				case self::MODE_DIRECT:
					if ($this->isCombo($resource)) {
						throw new Exceptions\InvalidResourceAliasException();
					}
					$data = new \stdClass();
					$data->url = $this->getFilePath($resource);
					$cwd = getcwd();
					if (!$cwd) {
						throw new Exceptions\ShouldNotHappenException();
					}
					$data->filename = sprintf('%s/%s/%s', rtrim($cwd, '/'), trim($this->localPrefix['path'], '/'), $data->url);
					break;
				case self::MODE_BUILD:
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
					throw new Exceptions\UnknownModeException('Unknown local file mode: ' . $this->localMode);
			}
			$this->localResources[$this->localMode][$resource] = $data;
		}
		return $this->localResources[$this->localMode][$resource];
	}


	/**
	 * Whether the resource is a combination one (e.g. foo+bar).
	 *
	 * @param string $resource
	 * @return boolean
	 */
	private function isCombo(string $resource): bool
	{
		return (strpos($resource, self::BUILD_SEPARATOR) !== false);
	}


	private function getFilePath(string $resource): string
	{
		if (is_array($this->resources[$resource])) {
			throw new Exceptions\ShouldNotHappenException();
		}
		return ltrim($this->resources[$resource], '/');
	}

}
