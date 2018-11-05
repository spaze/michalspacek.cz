<?php
declare(strict_types = 1);

namespace Spaze\SubresourceIntegrity;

/**
 * SubresourceIntegrity\Config service.
 *
 * @author Michal Špaček
 */
class Config
{

	/** @internal direct access to local files */
	const MODE_DIRECT = 'direct';

	/** @internal build local files, new file for every new resource version */
	const MODE_BUILD = 'build';

	/** @internal separator between multiple resources */
	const BUILD_SEPARATOR = '+';

	/** @var FileBuilder */
	private $fileBuilder;

	/** @var array of key => array of resources */
	protected $resources = array();

	/** @var array of (url => prefix, path => prefix, build => prefix) */
	protected $localPrefix = array(
		'url' => '',
		'path' => '',
		'build' => '',
	);

	/** @var string */
	protected $localMode = self::MODE_DIRECT;

	/** @var array of hashing algorithms */
	protected $hashingAlgos = array();

	/** @var array of resource => \stdClass (hash, filename) */
	protected $localResources = array();


	public function __construct(FileBuilder $fileBuilder)
	{
		$this->fileBuilder = $fileBuilder;
	}

	/**
	 * Set resources.
	 *
	 * @param array $resources
	 */
	public function setResources(array $resources)
	{
		$this->resources = $resources;
	}


	/**
	 * Set prefix for local resources.
	 *
	 * @param array $prefix
	 */
	public function setLocalPrefix(array $prefix)
	{
		foreach (array_keys($this->localPrefix) as $key) {
			if (isset($prefix[$key])) {
				$this->localPrefix[$key] = $prefix[$key];
			}
		}
	}


	/**
	 * Set local mode.
	 *
	 * @param string $mode
	 */
	public function setLocalMode(string $mode)
	{
		$this->localMode = $mode;
	}


	/**
	 * Set one or more hashing algorithms.
	 *
	 * @param string[] $algos
	 */
	public function setHashingAlgos(array $algos)
	{
		$this->hashingAlgos = $algos;
	}


	/**
	 * Get full URL for a resource.
	 *
	 * @param string $resource
	 * @return string
	 */
	public function getUrl(string $resource): string
	{
		if (!$this->isCombo($resource) && is_array($this->resources[$resource])) {
			$url = $this->resources[$resource]['url'];
		} else {
			$url = sprintf('%s/%s',
				rtrim($this->localPrefix['url'], '/'),
				$this->localFile($resource)->url
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
	public function getHash(string $resource): string
	{
		if (!$this->isCombo($resource) && is_array($this->resources[$resource])) {
			if (is_array($this->resources[$resource]['hash'])) {
				$hash = implode(' ', $this->resources[$resource]['hash']);
			} else {
				$hash = $this->resources[$resource]['hash'];
			}
		} else {
			$fileHashes = array();
			foreach ($this->hashingAlgos as $algo) {
				$fileHashes[] = $algo . '-' . base64_encode(hash_file($algo, $this->localFile($resource)->filename, true));
			}
			$hash = implode(' ', $fileHashes);
		}
		return $hash;
	}


	/**
	 * Get local file data.
	 *
	 * @param string $resource
	 * @return \stdClass
	 */
	private function localFile(string $resource): \stdClass
	{
		if (empty($this->localResources[$this->localMode][$resource])) {
			switch ($this->localMode) {
				case self::MODE_DIRECT:
					$data = new \stdClass();
					$data->url = ltrim($this->resources[$resource], '/');
					$data->filename = sprintf('%s/%s/%s', rtrim(getcwd(), '/'), trim($this->localPrefix['path'], '/'), $data->url);
					break;
				case self::MODE_BUILD:
					$resources = [];
					foreach (explode(self::BUILD_SEPARATOR, $resource) as $value) {
						$resources[] = $this->resources[$value];
					}
					$data = $this->fileBuilder->build($resources, $this->localPrefix['path'], $this->localPrefix['build']);
					break;
				default:
					throw new Exceptions\UnknownModeException('Unknown local file mode: ' . $this->localMode);
					break;
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

}
